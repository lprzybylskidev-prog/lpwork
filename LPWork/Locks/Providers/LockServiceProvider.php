<?php

declare(strict_types=1);

namespace LPWork\Locks\Providers;

use LPWork\Cache\CacheManager;
use LPWork\Config\ArrayConfigReader;
use LPWork\Config\Config;
use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Database\DatabaseManager;
use LPWork\Foundation\ServiceProvider;
use LPWork\Health\Checks\LocksHealthCheck;
use LPWork\Locks\AtomicLockManager;
use LPWork\Locks\CacheLockStore;
use LPWork\Locks\Contracts\LockStore;
use LPWork\Locks\DatabaseLockStore;
use LPWork\Locks\Exceptions\InvalidLockConfigException;
use LPWork\Locks\Exceptions\MissingLockConfigException;
use LPWork\Locks\Migrations\CreateLocksTable;
use LPWork\Locks\RedisLockStore;
use LPWork\Shared\Redis\RedisClient;
use LPWork\Shared\Redis\RedisConfigFactory;
use LPWork\Time\Contracts\Clock;

/**
 * Registers lock service provider services with the framework container.
 */
final class LockServiceProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $container->singleton(LockStore::class, static function (Container $container): LockStore {
            $cache = $container->make(CacheManager::class);
            $database = self::optional($container, DatabaseManager::class);
            $clock = self::optional($container, Clock::class);

            if (!$cache instanceof CacheManager) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(CacheManager::class);
            }

            if ($database !== null && !$database instanceof DatabaseManager) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(DatabaseManager::class);
            }

            if ($clock !== null && !$clock instanceof Clock) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Clock::class);
            }

            $reader = self::reader(Config::getArray('locks'));

            return match ($reader->optionalString('driver', 'driver') ?? 'cache') {
                'cache' => new CacheLockStore($cache->store($reader->string('store', 'store'))),
                'redis' => new RedisLockStore(new RedisClient(new RedisConfigFactory()->create($reader, Config::getArray('locks'), 'locks'), 'locks')),
                'database' => $database instanceof DatabaseManager ? new DatabaseLockStore(
                    connection: $database->connection(self::connectionName($reader)),
                    table: $reader->string('table', 'table'),
                    clock: $clock ?? new \LPWork\Time\SystemClock(),
                ) : throw new InvalidLockConfigException('database'),
                default => throw new InvalidLockConfigException('driver'),
            };
        });

        $container->singleton(AtomicLockManager::class, static function (Container $container): AtomicLockManager {
            $store = $container->make(LockStore::class);

            if (!$store instanceof LockStore) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(LockStore::class);
            }

            $reader = self::reader(Config::getArray('locks'));

            return new AtomicLockManager($store, $reader->int('ttl_seconds', 'ttl_seconds'));
        });

        $container->singleton(LocksHealthCheck::class);
        $this->registerHealthCheck($container, LocksHealthCheck::class);
        $this->registerLockMigrations($container);
    }

    private function registerLockMigrations(Container $container): void
    {
        $config = Config::getArray('locks');
        $reader = self::reader($config);

        if (($reader->optionalString('driver', 'driver') ?? 'cache') !== 'database') {
            return;
        }

        $table = $reader->string('table', 'table');
        $connection = $reader->optionalString('connection', 'connection', allowEmpty: true);
        $container->singleton(CreateLocksTable::class, static fn(): CreateLocksTable => new CreateLocksTable($table));
        parent::registerFrameworkMigrations($container, $connection !== null && $connection !== '' ? $connection : 'default', [CreateLocksTable::class]);
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private static function reader(array $config): ArrayConfigReader
    {
        return new ArrayConfigReader(
            config: $config,
            missingException: static fn(string $key): MissingLockConfigException => new MissingLockConfigException($key),
            invalidException: static fn(string $key): InvalidLockConfigException => new InvalidLockConfigException($key),
        );
    }

    private static function connectionName(ArrayConfigReader $reader): string
    {
        $connection = $reader->optionalString('connection', 'connection', allowEmpty: true);

        return $connection === null || $connection === '' ? 'default' : $connection;
    }
}
