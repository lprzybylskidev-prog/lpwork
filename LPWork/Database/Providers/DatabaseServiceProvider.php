<?php

declare(strict_types=1);

namespace LPWork\Database\Providers;

use LPWork\Config\Config;
use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Database\CompositeQueryReporter;
use LPWork\Database\Contracts\Connection;
use LPWork\Database\Contracts\QueryReporter;
use LPWork\Database\DatabaseDebugCollector;
use LPWork\Database\DatabaseDebugContextProvider;
use LPWork\Database\DatabaseManager;
use LPWork\Database\LoggingQueryReporter;
use LPWork\Foundation\Application;
use LPWork\Foundation\ServiceProvider;
use LPWork\Health\Checks\DatabaseHealthCheck;
use LPWork\Logging\Enums\LogLevel;
use LPWork\Logging\LogManager;
use LPWork\Observability\MetricCollector;

/**
 * Registers database service provider services with the framework container.
 */
final class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $container->singleton(DatabaseDebugCollector::class, static function (Container $container): DatabaseDebugCollector {
            $metrics = $container->has(MetricCollector::class) ? $container->make(MetricCollector::class) : null;

            if ($metrics !== null && !$metrics instanceof MetricCollector) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(MetricCollector::class);
            }

            return new DatabaseDebugCollector(metrics: $metrics);
        });
        $container->singleton(QueryReporter::class, static function (Container $container): QueryReporter {
            $reporters = [];
            $collector = $container->make(DatabaseDebugCollector::class);

            if (!$collector instanceof DatabaseDebugCollector) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(DatabaseDebugCollector::class);
            }

            $reporters[] = $collector;

            if (Config::getBool('database.logging.enabled')) {
                $manager = $container->make(LogManager::class);

                if (!$manager instanceof LogManager) {
                    throw CannotResolveDependencyException::factoryDidNotReturnObject(LogManager::class);
                }

                $level = LogLevel::tryFrom(Config::getString('database.logging.level'));

                if ($level === null) {
                    $level = LogLevel::Debug;
                }

                $reporters[] = new LoggingQueryReporter(
                    logger: $manager->channel(Config::getString('database.logging.channel')),
                    level: $level,
                    appDebug: Config::getBool('app.debug'),
                );
            }

            return new CompositeQueryReporter($reporters);
        });
        $container->singleton(DatabaseManager::class, static function (Container $container): DatabaseManager {
            $app = $container->make(Application::class);
            $reporter = $container->make(QueryReporter::class);

            if (!$app instanceof Application) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Application::class);
            }

            if (!$reporter instanceof QueryReporter) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(QueryReporter::class);
            }

            return new DatabaseManager(
                config: Config::getArray('database'),
                basePath: $app->basePath(),
                reporter: $reporter,
            );
        });
        $container->singleton(Connection::class, static function (Container $container): Connection {
            $manager = $container->make(DatabaseManager::class);

            if (!$manager instanceof DatabaseManager) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(DatabaseManager::class);
            }

            return $manager->default();
        });
        $container->singleton(DatabaseDebugContextProvider::class, static function (Container $container): DatabaseDebugContextProvider {
            $collector = $container->make(DatabaseDebugCollector::class);

            if (!$collector instanceof DatabaseDebugCollector) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(DatabaseDebugCollector::class);
            }

            return new DatabaseDebugContextProvider($collector, Config::getBool('app.debug'));
        });

        $this->registerHttpDebugContextProvider(
            $container,
            static function (Container $container): DatabaseDebugContextProvider {
                $provider = $container->make(DatabaseDebugContextProvider::class);

                if (!$provider instanceof DatabaseDebugContextProvider) {
                    throw CannotResolveDependencyException::factoryDidNotReturnObject(DatabaseDebugContextProvider::class);
                }

                return $provider;
            },
        );

        $container->singleton(DatabaseHealthCheck::class);
        $this->registerHealthCheck($container, DatabaseHealthCheck::class);
    }
}
