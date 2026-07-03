<?php

declare(strict_types=1);

namespace LPWork\Queue\Providers;

use LPWork\Config\ArrayConfigReader;
use LPWork\Config\Config;
use LPWork\Console\Commands\QueueClearCommand;
use LPWork\Console\Commands\QueuePruneCommand;
use LPWork\Console\Commands\QueueWorkCommand;
use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Database\DatabaseManager;
use LPWork\Events\EventDispatcher;
use LPWork\Foundation\ServiceProvider;
use LPWork\Health\Checks\QueueHealthCheck;
use LPWork\Logging\Contracts\Logger;
use LPWork\Observability\MetricCollector;
use LPWork\Queue\Exceptions\InvalidQueueConfigException;
use LPWork\Queue\Exceptions\MissingQueueConfigException;
use LPWork\Queue\Migrations\CreateQueueJobsTable;
use LPWork\Queue\QueueDebugCollector;
use LPWork\Queue\QueueDebugContextProvider;
use LPWork\Queue\QueueDriverFactory;
use LPWork\Queue\QueueJobRunner;
use LPWork\Queue\QueueManager;
use LPWork\Queue\QueuePruner;
use LPWork\Queue\QueueWorker;
use LPWork\Time\Contracts\Clock;

/**
 * Registers queue service provider services with the framework container.
 */
final class QueueServiceProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $container->singleton(QueueDebugCollector::class, static function (Container $container): QueueDebugCollector {
            $metrics = $container->has(MetricCollector::class) ? $container->make(MetricCollector::class) : null;

            if ($metrics !== null && !$metrics instanceof MetricCollector) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(MetricCollector::class);
            }

            return new QueueDebugCollector(metrics: $metrics);
        });

        $container->singleton(QueueJobRunner::class, static function (Container $container): QueueJobRunner {
            return new QueueJobRunner($container);
        });

        $container->singleton(QueueManager::class, static function (Container $container): QueueManager {
            $runner = $container->make(QueueJobRunner::class);
            $clock = $container->make(Clock::class);
            $database = $container->make(DatabaseManager::class);
            $events = self::optional($container, EventDispatcher::class);
            $collector = $container->make(QueueDebugCollector::class);

            if (!$runner instanceof QueueJobRunner) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(QueueJobRunner::class);
            }

            if (!$clock instanceof Clock) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Clock::class);
            }

            if (!$database instanceof DatabaseManager) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(DatabaseManager::class);
            }

            if (!$collector instanceof QueueDebugCollector) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(QueueDebugCollector::class);
            }

            return new QueueManager(
                config: Config::getArray('queue'),
                driverFactory: new QueueDriverFactory($runner, $clock, $database),
                clock: $clock,
                events: $events instanceof EventDispatcher ? $events : null,
                debugCollector: $collector,
            );
        });

        $container->singleton(QueueWorker::class, static function (Container $container): QueueWorker {
            $queues = $container->make(QueueManager::class);
            $runner = $container->make(QueueJobRunner::class);
            $events = self::optional($container, EventDispatcher::class);
            $logger = self::optional($container, Logger::class);
            $collector = $container->make(QueueDebugCollector::class);

            if (!$queues instanceof QueueManager) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(QueueManager::class);
            }

            if (!$runner instanceof QueueJobRunner) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(QueueJobRunner::class);
            }

            if (!$collector instanceof QueueDebugCollector) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(QueueDebugCollector::class);
            }

            return new QueueWorker(
                queues: $queues,
                runner: $runner,
                events: $events instanceof EventDispatcher ? $events : null,
                logger: $logger instanceof Logger ? $logger : null,
                debugCollector: $collector,
            );
        });

        $container->singleton(QueuePruner::class);
        $container->singleton(QueueHealthCheck::class);

        $container->singleton(QueueWorkCommand::class, static function (Container $container): QueueWorkCommand {
            $worker = $container->make(QueueWorker::class);
            $queues = $container->make(QueueManager::class);

            if (!$worker instanceof QueueWorker) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(QueueWorker::class);
            }

            if (!$queues instanceof QueueManager) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(QueueManager::class);
            }

            return new QueueWorkCommand($worker, $queues);
        });

        $container->singleton(QueuePruneCommand::class, static function (Container $container): QueuePruneCommand {
            $pruner = $container->make(QueuePruner::class);

            if (!$pruner instanceof QueuePruner) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(QueuePruner::class);
            }

            return new QueuePruneCommand($pruner);
        });

        $container->singleton(QueueClearCommand::class, static function (Container $container): QueueClearCommand {
            $queues = $container->make(QueueManager::class);

            if (!$queues instanceof QueueManager) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(QueueManager::class);
            }

            return new QueueClearCommand($queues);
        });

        $this->registerCommands($container, [
            QueueClearCommand::class,
            QueuePruneCommand::class,
            QueueWorkCommand::class,
        ]);

        $this->registerQueueMigrations($container);
        $this->registerDebugContext($container);
        $this->registerHealthCheck($container, QueueHealthCheck::class);
    }

    private function registerDebugContext(Container $container): void
    {
        $this->registerHttpDebugContextProvider(
            $container,
            static function (Container $container): QueueDebugContextProvider {
                $collector = $container->make(QueueDebugCollector::class);

                if (!$collector instanceof QueueDebugCollector) {
                    throw CannotResolveDependencyException::factoryDidNotReturnObject(QueueDebugCollector::class);
                }

                return new QueueDebugContextProvider($collector);
            },
        );
    }

    private function registerQueueMigrations(Container $container): void
    {
        $config = Config::getArray('queue');
        $reader = new ArrayConfigReader(
            config: $config,
            missingException: static fn(string $key): MissingQueueConfigException => new MissingQueueConfigException($key),
            invalidException: static fn(string $key): InvalidQueueConfigException => new InvalidQueueConfigException($key),
        );
        $connections = $reader->arrayMap('connections');
        $database = $connections['database'] ?? null;

        if ($database === null) {
            return;
        }

        $databaseReader = $this->reader($database);
        $table = $databaseReader->string('table', 'connections.database.table');
        $connection = $database['connection'] ?? null;
        $container->singleton(CreateQueueJobsTable::class, static fn(): CreateQueueJobsTable => new CreateQueueJobsTable($table));
        parent::registerFrameworkMigrations(
            $container,
            is_string($connection) && $connection !== '' ? $connection : 'default',
            [CreateQueueJobsTable::class],
        );
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private function reader(array $config): ArrayConfigReader
    {
        return new ArrayConfigReader(
            config: $config,
            missingException: static fn(string $key): MissingQueueConfigException => new MissingQueueConfigException($key),
            invalidException: static fn(string $key): InvalidQueueConfigException => new InvalidQueueConfigException($key),
        );
    }
}
