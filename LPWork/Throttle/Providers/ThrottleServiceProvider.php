<?php

declare(strict_types=1);

namespace LPWork\Throttle\Providers;

use LPWork\Cache\CacheManager;
use LPWork\Config\ArrayConfigReader;
use LPWork\Config\Config;
use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Events\EventRegistry;
use LPWork\Foundation\ServiceProvider;
use LPWork\Health\Checks\ThrottleHealthCheck;
use LPWork\Kernels\Http\HttpThrottleMiddlewareResolver;
use LPWork\Observability\MetricCollector;
use LPWork\Shared\Redis\RedisClient;
use LPWork\Shared\Redis\RedisConfigFactory;
use LPWork\Throttle\CliThrottle;
use LPWork\Throttle\Contracts\ThrottleClock;
use LPWork\Throttle\Contracts\ThrottleStorage;
use LPWork\Throttle\Events\CliCommandThrottled;
use LPWork\Throttle\Events\HttpRequestThrottled;
use LPWork\Throttle\Exceptions\InvalidThrottleConfigException;
use LPWork\Throttle\Exceptions\MissingThrottleConfigException;
use LPWork\Throttle\Exceptions\UnsupportedThrottleStorageException;
use LPWork\Throttle\Listeners\RecordCliCommandThrottled;
use LPWork\Throttle\Listeners\RecordHttpRequestThrottled;
use LPWork\Throttle\Storage\CacheThrottleStorage;
use LPWork\Throttle\Storage\InMemoryThrottleStorage;
use LPWork\Throttle\Storage\RedisThrottleStorage;
use LPWork\Throttle\SystemThrottleClock;
use LPWork\Throttle\ThrottleConfig;
use LPWork\Throttle\ThrottleConfigFactory;
use LPWork\Throttle\ThrottleDebugCollector;
use LPWork\Throttle\ThrottleDebugContextProvider;
use LPWork\Throttle\ThrottleLimiter;

/**
 * Registers throttle service provider services with the framework container.
 */
final class ThrottleServiceProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $container->singleton(ThrottleConfigFactory::class);
        $container->singleton(ThrottleDebugCollector::class, static function (Container $container): ThrottleDebugCollector {
            $metrics = $container->has(MetricCollector::class) ? $container->make(MetricCollector::class) : null;

            if ($metrics !== null && !$metrics instanceof MetricCollector) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(MetricCollector::class);
            }

            return new ThrottleDebugCollector(metrics: $metrics);
        });
        $container->singleton(ThrottleClock::class, SystemThrottleClock::class);
        $container->singleton(HttpThrottleMiddlewareResolver::class);
        $container->singleton(CliThrottle::class);

        $container->singleton(ThrottleConfig::class, static function (Container $container): ThrottleConfig {
            $factory = $container->make(ThrottleConfigFactory::class);

            if (!$factory instanceof ThrottleConfigFactory) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(ThrottleConfigFactory::class);
            }

            return $factory->create(Config::getArray('throttle'));
        });

        $container->singleton(ThrottleStorage::class, static function (Container $container): ThrottleStorage {
            $config = $container->make(ThrottleConfig::class);

            if (!$config instanceof ThrottleConfig) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(ThrottleConfig::class);
            }

            $cache = self::optional($container, CacheManager::class);

            if ($cache !== null && !$cache instanceof CacheManager) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(CacheManager::class);
            }

            $rawConfig = Config::getArray('throttle');
            $reader = new ArrayConfigReader(
                config: $rawConfig,
                missingException: static fn(string $key): MissingThrottleConfigException => new MissingThrottleConfigException($key),
                invalidException: static fn(string $key): InvalidThrottleConfigException => new InvalidThrottleConfigException($key),
            );

            return match ($config->storage()) {
                'memory' => new InMemoryThrottleStorage(),
                'cache' => $cache instanceof CacheManager
                    ? new CacheThrottleStorage($cache->store($reader->string('store', 'store')))
                    : throw new UnsupportedThrottleStorageException($config->storage()),
                'redis' => new RedisThrottleStorage(new RedisClient(new RedisConfigFactory()->create($reader, $rawConfig, 'throttle'), 'throttle')),
                default => throw new UnsupportedThrottleStorageException($config->storage()),
            };
        });

        $container->singleton(ThrottleLimiter::class);
        $container->singleton(ThrottleHealthCheck::class, static fn(Container $container): ThrottleHealthCheck => new ThrottleHealthCheck($container));
        $this->registerDiagnostics($container);
        $this->registerHealthCheck($container, ThrottleHealthCheck::class);
    }

    private function registerDiagnostics(Container $container): void
    {
        if ($container->has(EventRegistry::class)) {
            $registry = $container->make(EventRegistry::class);

            if ($registry instanceof EventRegistry) {
                $registry->add(HttpRequestThrottled::class, [RecordHttpRequestThrottled::class]);
                $registry->add(CliCommandThrottled::class, [RecordCliCommandThrottled::class]);
            }
        }

        $this->registerHttpDebugContextProvider(
            $container,
            static function (Container $container): ThrottleDebugContextProvider {
                $collector = $container->make(ThrottleDebugCollector::class);

                if (!$collector instanceof ThrottleDebugCollector) {
                    throw CannotResolveDependencyException::factoryDidNotReturnObject(ThrottleDebugCollector::class);
                }

                return new ThrottleDebugContextProvider($collector);
            },
        );
    }
}
