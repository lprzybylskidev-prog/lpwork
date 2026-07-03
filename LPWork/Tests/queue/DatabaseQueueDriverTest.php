<?php

declare(strict_types=1);

use LPWork\Queue\DatabaseQueueRepository;
use LPWork\Queue\Enums\QueueJobStatus;
use LPWork\Queue\QueueDispatchOptions;
use LPWork\Queue\QueuePruner;
use LPWork\Queue\QueueWorker;
use LPWork\Queue\QueueWorkerOptions;
use Tests\support\queue\FailingJob;
use Tests\support\queue\QueueDatabaseHarness;
use Tests\support\queue\QueueTableRows;
use Tests\support\queue\RecordingJob;

it('stores database jobs and lets a worker complete them', function (): void {
    $harness = QueueDatabaseHarness::create();
    $path = $harness->database->basePath() . '/job.log';

    try {
        $harness->manager->dispatch(new RecordingJob($path, 'stored'));
        $worker = new QueueWorker($harness->manager, $harness->runner);
        $result = $worker->work(new QueueWorkerOptions('database', 'default', once: true));
        $rows = new DatabaseQueueRepository($harness->databaseManager->default(), $harness->clock)->all();
        $row = QueueTableRows::firstArray($rows);

        expect($result->processed)->toBe(1)
            ->and($result->failed)->toBe(0)
            ->and(file_get_contents($path))->toBe("stored\n")
            ->and($rows)->toHaveCount(1)
            ->and($row['status'])->toBe(QueueJobStatus::Completed->value);
    } finally {
        $harness->database->remove();
    }
});

it('releases failing jobs until max attempts then marks them failed', function (): void {
    $harness = QueueDatabaseHarness::create();

    try {
        $harness->manager->dispatch(new FailingJob('nope'), new QueueDispatchOptions(maxAttempts: 2));
        $worker = new QueueWorker($harness->manager, $harness->runner);

        $first = $worker->work(new QueueWorkerOptions('database', 'default', once: true));
        $harness->clock->travel(10);
        $second = $worker->work(new QueueWorkerOptions('database', 'default', once: true));

        $rows = new DatabaseQueueRepository($harness->databaseManager->default(), $harness->clock)->all();
        $row = QueueTableRows::firstArray($rows);

        expect($first->processed)->toBe(0)
            ->and($first->failed)->toBe(0)
            ->and($second->failed)->toBe(1)
            ->and($row['status'])->toBe(QueueJobStatus::Failed->value)
            ->and($row['attempts'])->toBe(2);
    } finally {
        $harness->database->remove();
    }
});

it('prunes retained completed and failed database jobs', function (): void {
    $harness = QueueDatabaseHarness::create();
    $path = $harness->database->basePath() . '/job.log';

    try {
        $harness->manager->dispatch(new RecordingJob($path, 'done'));
        $harness->manager->dispatch(new FailingJob('dead'), new QueueDispatchOptions(maxAttempts: 1));
        $worker = new QueueWorker($harness->manager, $harness->runner);
        $worker->work(new QueueWorkerOptions('database', 'default', maxJobs: 2));
        $harness->clock->travel(61);

        $pruned = new QueuePruner($harness->manager)->prune('database');
        $rows = new DatabaseQueueRepository($harness->databaseManager->default(), $harness->clock)->all();

        expect($pruned)->toBe(['completed' => 1, 'failed' => 1])
            ->and($rows)->toBe([]);
    } finally {
        $harness->database->remove();
    }
});
