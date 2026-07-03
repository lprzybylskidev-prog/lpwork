<?php

declare(strict_types=1);

namespace Tests\support\queue;

use LPWork\Container\Container;
use LPWork\Database\DatabaseManager;
use LPWork\Queue\Migrations\CreateQueueJobsTable;
use LPWork\Queue\QueueDriverFactory;
use LPWork\Queue\QueueJobRunner;
use LPWork\Queue\QueueManager;
use Tests\support\database\SqliteDatabase;

final readonly class QueueDatabaseHarness
{
    private function __construct(
        public SqliteDatabase $database,
        public MutableClock $clock,
        public DatabaseManager $databaseManager,
        public QueueJobRunner $runner,
        public QueueManager $manager,
    ) {}

    public static function create(): self
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
        new CreateQueueJobsTable('queue_jobs')->up($databaseManager->default());
        $manager = new QueueManager(
            config: [
                'default' => 'database',
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
                    'database' => [
                        'driver' => 'database',
                        'connection' => 'sqlite',
                        'table' => 'queue_jobs',
                    ],
                ],
            ],
            driverFactory: new QueueDriverFactory($runner, $clock, $databaseManager),
            clock: $clock,
        );

        return new self($database, $clock, $databaseManager, $runner, $manager);
    }
}
