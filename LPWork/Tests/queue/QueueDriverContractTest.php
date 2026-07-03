<?php

declare(strict_types=1);

use LPWork\Container\Container;
use LPWork\Database\DatabaseManager;
use LPWork\Queue\DatabaseQueueRepository;
use LPWork\Queue\Drivers\DatabaseQueueDriver;
use LPWork\Queue\Drivers\SyncQueueDriver;
use LPWork\Queue\Migrations\CreateQueueJobsTable;
use LPWork\Queue\QueuedJobPayload;
use LPWork\Queue\QueueJobRunner;
use LPWork\Queue\QueuePayloadSerializer;
use Tests\support\database\SqliteDatabase;
use Tests\support\queue\MutableClock;
use Tests\support\testing\Queue\NullQueueJob;
use Tests\support\testing\Queue\QueueDriverContract;

it('keeps the sync queue driver compatible with the shared immediate queue contract', function (): void {
    $driver = new SyncQueueDriver(new QueueJobRunner(new Container()));
    $job = new NullQueueJob();
    $payload = new QueuedJobPayload(
        id: 'contract-sync-1',
        queue: 'default',
        jobClass: $job::class,
        body: new QueuePayloadSerializer()->serialize($job),
        maxAttempts: 1,
        availableAt: 1000,
        createdAt: 1000,
    );

    new QueueDriverContract($driver)->verifiesImmediateQueueBehavior($payload);
});

it('keeps the database queue driver compatible with the shared reservable queue contract', function (): void {
    $database = SqliteDatabase::create();

    try {
        $clock = new MutableClock();
        $connection = new DatabaseManager([
            'default' => 'sqlite',
            'connections' => [
                'sqlite' => [
                    'driver' => 'sqlite',
                    'database' => $database->relativePath(),
                ],
            ],
        ], $database->basePath())->default();
        new CreateQueueJobsTable('queue_jobs')->up($connection);

        $driver = new DatabaseQueueDriver(new DatabaseQueueRepository($connection, $clock));
        new QueueDriverContract($driver, $clock->travel(...))->verifiesReservableQueueBehavior();
    } finally {
        $database->remove();
    }
});
