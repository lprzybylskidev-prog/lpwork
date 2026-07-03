<?php

declare(strict_types=1);

namespace LPWork\Schedule\Providers;

use LPWork\Config\ArrayConfigReader;
use LPWork\Config\Config;
use LPWork\Console\ConsoleTableRenderer;
use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Database\DatabaseManager;
use LPWork\Events\EventDispatcher;
use LPWork\Foundation\ServiceProvider;
use LPWork\Health\Checks\SchedulerHealthCheck;
use LPWork\Locks\AtomicLockManager;
use LPWork\Logging\Contracts\Logger;
use LPWork\Observability\MetricCollector;
use LPWork\Schedule\Commands\ScheduleListCommand;
use LPWork\Schedule\Commands\SchedulePruneCommand;
use LPWork\Schedule\Commands\ScheduleRunCommand;
use LPWork\Schedule\Exceptions\InvalidScheduleConfigException;
use LPWork\Schedule\Exceptions\MissingScheduleConfigException;
use LPWork\Schedule\Migrations\CreateScheduleRunsTable;
use LPWork\Schedule\ScheduledCommandExecutor;
use LPWork\Schedule\ScheduleDebugCollector;
use LPWork\Schedule\ScheduleDebugContextProvider;
use LPWork\Schedule\ScheduledJobExecutor;
use LPWork\Schedule\ScheduledTaskExecutorRegistry;
use LPWork\Schedule\ScheduleListRenderer;
use LPWork\Schedule\SchedulePruner;
use LPWork\Schedule\ScheduleRegistry;
use LPWork\Schedule\ScheduleRunner;
use LPWork\Schedule\ScheduleStore;
use LPWork\Schedule\ScheduleStoreFactory;
use LPWork\Time\Contracts\Clock;

/**
 * Registers scheduler service provider services with the framework container.
 */
final class SchedulerServiceProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $container->singleton(ScheduleDebugCollector::class, static function (Container $container): ScheduleDebugCollector {
            $metrics = $container->has(MetricCollector::class) ? $container->make(MetricCollector::class) : null;

            if ($metrics !== null && !$metrics instanceof MetricCollector) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(MetricCollector::class);
            }

            return new ScheduleDebugCollector(metrics: $metrics);
        });

        $container->singleton(ScheduleRegistry::class);
        $container->singleton(SchedulerHealthCheck::class);
        $container->singleton(ScheduledCommandExecutor::class);
        $container->singleton(ScheduledJobExecutor::class);
        $container->singleton(ScheduledTaskExecutorRegistry::class, static function (Container $container): ScheduledTaskExecutorRegistry {
            $registry = new ScheduledTaskExecutorRegistry();
            $command = $container->make(ScheduledCommandExecutor::class);
            $job = $container->make(ScheduledJobExecutor::class);

            if (!$command instanceof ScheduledCommandExecutor) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(ScheduledCommandExecutor::class);
            }

            if (!$job instanceof ScheduledJobExecutor) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(ScheduledJobExecutor::class);
            }

            $registry->add($command);
            $registry->add($job);

            return $registry;
        });
        $container->singleton(ScheduleStoreFactory::class, static function (Container $container): ScheduleStoreFactory {
            $database = $container->make(DatabaseManager::class);
            $clock = $container->make(Clock::class);

            if (!$database instanceof DatabaseManager) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(DatabaseManager::class);
            }

            if (!$clock instanceof Clock) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Clock::class);
            }

            $reader = self::reader(Config::getArray('schedule'));
            $databaseConfig = self::reader($reader->array('database'));
            $historyConfig = self::reader($reader->array('history'));
            $connection = $databaseConfig->string('connection', 'database.connection');

            return new ScheduleStoreFactory(
                database: $database,
                clock: $clock,
                connection: $connection,
                runsTable: $databaseConfig->string('runs_table', 'database.runs_table'),
                historyEnabled: $historyConfig->bool('enabled', 'history.enabled'),
            );
        });
        $container->singleton(ScheduleStore::class, static function (Container $container): ScheduleStore {
            $factory = $container->make(ScheduleStoreFactory::class);

            if (!$factory instanceof ScheduleStoreFactory) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(ScheduleStoreFactory::class);
            }

            return $factory->create();
        });
        $container->singleton(ScheduleRunner::class, static function (Container $container): ScheduleRunner {
            $schedule = $container->make(ScheduleRegistry::class);
            $executors = $container->make(ScheduledTaskExecutorRegistry::class);
            $stores = $container->make(ScheduleStoreFactory::class);
            $locks = $container->make(AtomicLockManager::class);
            $clock = $container->make(Clock::class);
            $events = self::optional($container, EventDispatcher::class);
            $logger = self::optional($container, Logger::class);
            $collector = $container->make(ScheduleDebugCollector::class);

            if (!$schedule instanceof ScheduleRegistry) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(ScheduleRegistry::class);
            }

            if (!$executors instanceof ScheduledTaskExecutorRegistry) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(ScheduledTaskExecutorRegistry::class);
            }

            if (!$stores instanceof ScheduleStoreFactory) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(ScheduleStoreFactory::class);
            }

            if (!$locks instanceof AtomicLockManager) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(AtomicLockManager::class);
            }

            if (!$clock instanceof Clock) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Clock::class);
            }

            if (!$collector instanceof ScheduleDebugCollector) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(ScheduleDebugCollector::class);
            }

            $reader = self::reader(Config::getArray('schedule'));

            return new ScheduleRunner(
                schedule: $schedule,
                executors: $executors,
                stores: $stores,
                locks: $locks,
                clock: $clock,
                lockTtlSeconds: $reader->int('lock_ttl_seconds', 'lock_ttl_seconds'),
                events: $events instanceof EventDispatcher ? $events : null,
                logger: $logger instanceof Logger ? $logger : null,
                debugCollector: $collector,
            );
        });
        $container->singleton(SchedulePruner::class, static function (Container $container): SchedulePruner {
            $stores = $container->make(ScheduleStoreFactory::class);

            if (!$stores instanceof ScheduleStoreFactory) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(ScheduleStoreFactory::class);
            }

            $history = self::reader(self::reader(Config::getArray('schedule'))->array('history'));

            return new SchedulePruner($stores, $history->int('retention_seconds', 'history.retention_seconds'));
        });
        $container->singleton(ScheduleListRenderer::class, static function (Container $container): ScheduleListRenderer {
            $tables = $container->make(ConsoleTableRenderer::class);

            if (!$tables instanceof ConsoleTableRenderer) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(ConsoleTableRenderer::class);
            }

            return new ScheduleListRenderer($tables);
        });
        $container->singleton(ScheduleListCommand::class);
        $container->singleton(ScheduleRunCommand::class);
        $container->singleton(SchedulePruneCommand::class);

        $this->registerCommands($container, [
            ScheduleListCommand::class,
            SchedulePruneCommand::class,
            ScheduleRunCommand::class,
        ]);

        $this->registerHealthCheck($container, SchedulerHealthCheck::class);
        $this->registerSchedulerMigrations($container);
        $this->registerDebugContext($container);
    }

    private function registerDebugContext(Container $container): void
    {
        $this->registerHttpDebugContextProvider(
            $container,
            static function (Container $container): ScheduleDebugContextProvider {
                $collector = $container->make(ScheduleDebugCollector::class);

                if (!$collector instanceof ScheduleDebugCollector) {
                    throw CannotResolveDependencyException::factoryDidNotReturnObject(ScheduleDebugCollector::class);
                }

                return new ScheduleDebugContextProvider($collector);
            },
        );
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private static function reader(array $config): ArrayConfigReader
    {
        return new ArrayConfigReader(
            config: $config,
            missingException: static fn(string $key): MissingScheduleConfigException => new MissingScheduleConfigException($key),
            invalidException: static fn(string $key): InvalidScheduleConfigException => new InvalidScheduleConfigException($key),
        );
    }

    private function registerSchedulerMigrations(Container $container): void
    {
        $database = self::reader(self::reader(Config::getArray('schedule'))->array('database'));
        $table = $database->string('runs_table', 'database.runs_table');
        $container->singleton(CreateScheduleRunsTable::class, static fn(): CreateScheduleRunsTable => new CreateScheduleRunsTable($table));
        parent::registerFrameworkMigrations($container, $database->string('connection', 'database.connection'), [CreateScheduleRunsTable::class]);
    }
}
