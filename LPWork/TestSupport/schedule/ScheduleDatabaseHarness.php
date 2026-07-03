<?php

declare(strict_types=1);

namespace Tests\support\schedule;

use LPWork\Cache\CacheStore;
use LPWork\Cache\Drivers\FileCacheDriver;
use LPWork\Console\CommandRegistry;
use LPWork\Container\Container;
use LPWork\Database\DatabaseManager;
use LPWork\Locks\AtomicLockManager;
use LPWork\Locks\CacheLockStore;
use LPWork\Queue\QueueDriverFactory;
use LPWork\Queue\QueueJobRunner;
use LPWork\Queue\QueueManager;
use LPWork\Schedule\Migrations\CreateScheduleRunsTable;
use LPWork\Schedule\ScheduledCommandExecutor;
use LPWork\Schedule\ScheduledJobExecutor;
use LPWork\Schedule\ScheduledTaskExecutorRegistry;
use LPWork\Schedule\ScheduleRegistry;
use LPWork\Schedule\ScheduleRunner;
use LPWork\Schedule\ScheduleStore;
use LPWork\Schedule\ScheduleStoreFactory;
use Tests\support\database\SqliteDatabase;
use Tests\support\queue\MutableClock;

final readonly class ScheduleDatabaseHarness
{
    private function __construct(
        public SqliteDatabase $database,
        public MutableClock $clock,
        public DatabaseManager $databaseManager,
        public QueueManager $queueManager,
        public ScheduleRegistry $schedule,
        public ScheduleStore $store,
        public AtomicLockManager $locks,
        public ScheduleRunner $runner,
    ) {}

    public static function create(?CommandRegistry $commands = null): self
    {
        $database = SqliteDatabase::create();
        $clock = new MutableClock();
        $databaseManager = new DatabaseManager([
            'default' => 'sqlite',
            'connections' => [
                'sqlite' => [
                    'driver' => 'sqlite',
                    'database' => $database->relativePath(),
                ],
            ],
        ], $database->basePath());
        $container = new Container();
        $runner = new QueueJobRunner($container);
        $queueManager = new QueueManager(
            config: [
                'default' => 'sync',
                'queue' => 'default',
                'retry' => [
                    'max_attempts' => 2,
                    'retry_after_seconds' => 90,
                    'delay_seconds' => 10,
                ],
                'retention' => [
                    'completed_seconds' => 60,
                    'failed_seconds' => 60,
                ],
                'connections' => [
                    'sync' => [
                        'driver' => 'sync',
                    ],
                ],
            ],
            driverFactory: new QueueDriverFactory($runner, $clock, $databaseManager),
            clock: $clock,
        );

        new CreateScheduleRunsTable('schedule_runs')->up($databaseManager->default());

        $schedule = new ScheduleRegistry();
        $executors = new ScheduledTaskExecutorRegistry();
        $executors->add(new ScheduledCommandExecutor($commands ?? new CommandRegistry()));
        $executors->add(new ScheduledJobExecutor($queueManager));
        $store = new ScheduleStore($databaseManager->default(), $clock);
        $stores = new ScheduleStoreFactory($databaseManager, $clock, 'sqlite', 'schedule_runs', true);
        $locks = new AtomicLockManager(
            new CacheLockStore(new CacheStore('schedule', new FileCacheDriver('cache', $database->basePath(), clock: $clock))),
            60,
        );
        $scheduleRunner = new ScheduleRunner($schedule, $executors, $stores, $locks, $clock, lockTtlSeconds: 60);

        return new self($database, $clock, $databaseManager, $queueManager, $schedule, $store, $locks, $scheduleRunner);
    }
}
