<?php

declare(strict_types=1);

namespace LPWork\Logging\Providers;

use LPWork\Config\Config;
use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Foundation\Application;
use LPWork\Foundation\ServiceProvider;
use LPWork\Health\Checks\LoggingHealthCheck;
use LPWork\Logging\Contracts\Logger;
use LPWork\Logging\LogDriverFactory;
use LPWork\Logging\LogManager;
use LPWork\Observability\DiagnosticsCollector;
use LPWork\Observability\DiagnosticsLogger;
use LPWork\Storage\StorageManager;
use LPWork\Time\Contracts\Clock;
use LPWork\Time\SystemClock;

/**
 * Registers logging service provider services with the framework container.
 */
final class LoggingServiceProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $container->singleton(LogManager::class, static function (Container $container): LogManager {
            $app = $container->make(Application::class);
            $storage = $container->make(StorageManager::class);
            try {
                $clock = $container->make(Clock::class);
            } catch (CannotResolveDependencyException) {
                $clock = new SystemClock();
            }

            if (!$app instanceof Application) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Application::class);
            }

            if (!$storage instanceof StorageManager) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(StorageManager::class);
            }

            if (!$clock instanceof Clock) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Clock::class);
            }

            return new LogManager(
                config: Config::getArray('logging'),
                basePath: $app->basePath(),
                driverFactory: new LogDriverFactory($app->basePath(), storage: $storage),
                clock: $clock,
            );
        });

        $container->singleton(Logger::class, static function (Container $container): Logger {
            $manager = $container->make(LogManager::class);

            if (!$manager instanceof LogManager) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(LogManager::class);
            }

            $logger = $manager->default();

            if (!$container->has(DiagnosticsCollector::class)) {
                return $logger;
            }

            $collector = $container->make(DiagnosticsCollector::class);

            if (!$collector instanceof DiagnosticsCollector) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(DiagnosticsCollector::class);
            }

            return new DiagnosticsLogger($logger, $collector);
        });

        $container->singleton(LoggingHealthCheck::class);
        $this->registerHealthCheck($container, LoggingHealthCheck::class);
    }
}
