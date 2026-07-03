<?php

declare(strict_types=1);

namespace LPWork\Cache\Providers;

use LPWork\Cache\CacheClearer;
use LPWork\Cache\CacheDebugCollector;
use LPWork\Cache\CacheDriverFactory;
use LPWork\Cache\CacheManager;
use LPWork\Cache\CacheStore;
use LPWork\Cache\Exceptions\InvalidCacheConfigException;
use LPWork\Cache\Exceptions\MissingCacheConfigException;
use LPWork\Cache\Migrations\CreateCacheEntriesTable;
use LPWork\Config\ArrayConfigReader;
use LPWork\Config\Config;
use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Database\DatabaseManager;
use LPWork\Foundation\Application;
use LPWork\Foundation\ServiceProvider;
use LPWork\Health\Checks\CacheHealthCheck;
use LPWork\Observability\MetricCollector;
use LPWork\Storage\StorageManager;
use LPWork\Time\Contracts\Clock;

/**
 * Registers cache service provider services with the framework container.
 */
final class CacheServiceProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $container->singleton(CacheDebugCollector::class, static function (Container $container): CacheDebugCollector {
            $metrics = $container->has(MetricCollector::class) ? $container->make(MetricCollector::class) : null;

            if ($metrics !== null && !$metrics instanceof MetricCollector) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(MetricCollector::class);
            }

            return new CacheDebugCollector(metrics: $metrics);
        });

        $container->singleton(CacheManager::class, static function (Container $container): CacheManager {
            $app = $container->make(Application::class);
            $storage = $container->make(StorageManager::class);
            $database = self::optional($container, DatabaseManager::class);
            $clock = self::optional($container, Clock::class);
            $collector = $container->make(CacheDebugCollector::class);

            if (!$app instanceof Application) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Application::class);
            }

            if (!$storage instanceof StorageManager) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(StorageManager::class);
            }

            if ($database !== null && !$database instanceof DatabaseManager) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(DatabaseManager::class);
            }

            if ($clock !== null && !$clock instanceof Clock) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Clock::class);
            }

            if (!$collector instanceof CacheDebugCollector) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(CacheDebugCollector::class);
            }

            return new CacheManager(
                config: Config::getArray('cache'),
                basePath: $app->basePath(),
                driverFactory: new CacheDriverFactory($app->basePath(), $storage, $database, $clock ?? new \LPWork\Time\SystemClock()),
                debugCollector: $collector,
            );
        });

        $container->singleton(CacheStore::class, static function (Container $container): CacheStore {
            $manager = $container->make(CacheManager::class);

            if (!$manager instanceof CacheManager) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(CacheManager::class);
            }

            return $manager->default();
        });

        $container->singleton(CacheClearer::class);
        $container->singleton(CacheHealthCheck::class, static function (Container $container): CacheHealthCheck {
            $manager = $container->make(CacheManager::class);

            if (!$manager instanceof CacheManager) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(CacheManager::class);
            }

            return new CacheHealthCheck($manager);
        });

        $this->registerHealthCheck($container, CacheHealthCheck::class);
        $this->registerCacheMigrations($container);
    }

    private function registerCacheMigrations(Container $container): void
    {
        $reader = new ArrayConfigReader(
            config: Config::getArray('cache'),
            missingException: static fn(string $key): MissingCacheConfigException => new MissingCacheConfigException($key),
            invalidException: static fn(string $key): InvalidCacheConfigException => new InvalidCacheConfigException($key),
        );

        foreach ($reader->arrayMap('stores') as $name => $store) {
            $storeReader = new ArrayConfigReader(
                config: $store,
                missingException: static fn(string $key): MissingCacheConfigException => new MissingCacheConfigException($key),
                invalidException: static fn(string $key): InvalidCacheConfigException => new InvalidCacheConfigException($key),
            );

            if ($storeReader->string('driver', "stores.{$name}.driver") !== 'database') {
                continue;
            }

            $table = $storeReader->string('table', "stores.{$name}.table");
            $connection = $store['connection'] ?? null;
            $class = CreateCacheEntriesTable::class;
            $container->singleton($class, static fn(): CreateCacheEntriesTable => new CreateCacheEntriesTable($table));
            parent::registerFrameworkMigrations(
                $container,
                is_string($connection) && $connection !== '' ? $connection : 'default',
                [$class],
            );
        }
    }
}
