<?php

declare(strict_types=1);

use LPWork\Console\Commands\QueueClearCommand;
use LPWork\Console\Commands\QueuePruneCommand;
use LPWork\Console\Commands\QueueWorkCommand;
use LPWork\Console\Contracts\ProductionSensitiveCommand;
use LPWork\Console\Input;
use LPWork\Console\Middleware\ProductionSafetyMiddleware;
use LPWork\Console\Output;
use LPWork\Queue\DatabaseQueueRepository;
use LPWork\Queue\QueueDispatchOptions;
use LPWork\Queue\QueuePruner;
use LPWork\Queue\QueueWorker;
use Tests\support\console\OutputStreams;
use Tests\support\queue\FailingJob;
use Tests\support\queue\QueueDatabaseHarness;
use Tests\support\queue\RecordingJob;

it('processes queued jobs through the queue work command', function (): void {
    $harness = QueueDatabaseHarness::create();
    $path = $harness->database->basePath() . '/job.log';
    $streams = OutputStreams::create();

    try {
        $harness->manager->dispatch(new RecordingJob($path, 'command'));
        $command = new QueueWorkCommand(new QueueWorker($harness->manager, $harness->runner), $harness->manager);
        $exitCode = $command->handle(
            new Input(['lpwork', 'queue:work', '--once']),
            new Output($streams->stdout, $streams->stderr, decorated: false),
        );

        expect($exitCode)->toBe(0)
            ->and($streams->stdout())->toContain('OK Queue work complete.')
            ->and($streams->stdout())->toContain('| Processed  | 1')
            ->and(file_get_contents($path))->toBe("command\n");
    } finally {
        $harness->database->remove();
    }
});

it('prunes queue records through the queue prune command', function (): void {
    $harness = QueueDatabaseHarness::create();
    $path = $harness->database->basePath() . '/job.log';
    $streams = OutputStreams::create();

    try {
        $harness->manager->dispatch(new RecordingJob($path, 'done'));
        $harness->manager->dispatch(new FailingJob(), new QueueDispatchOptions(maxAttempts: 1));
        new QueueWorker($harness->manager, $harness->runner)->work(new \LPWork\Queue\QueueWorkerOptions('database', 'default', maxJobs: 2));
        $harness->clock->travel(61);

        $exitCode = new QueuePruneCommand(new QueuePruner($harness->manager))->handle(
            new Input(['lpwork', 'queue:prune', '--connection=database']),
            new Output($streams->stdout, $streams->stderr, decorated: false),
        );
        $rows = new DatabaseQueueRepository($harness->databaseManager->default(), $harness->clock)->all();

        expect($exitCode)->toBe(0)
            ->and($streams->stdout())->toContain('OK Queue pruned.')
            ->and($streams->stdout())->toContain('| Completed  | 1')
            ->and($streams->stdout())->toContain('| Failed     | 1')
            ->and($rows)->toBe([]);
    } finally {
        $harness->database->remove();
    }
});

it('marks queue clear as production sensitive middleware', function (): void {
    $harness = QueueDatabaseHarness::create();

    try {
        $command = new QueueClearCommand($harness->manager);

        expect($command)->toBeInstanceOf(ProductionSensitiveCommand::class)
            ->and($command->middleware())->toBe([ProductionSafetyMiddleware::class])
            ->and($command->productionSafetyMessage())->toContain('without --force');
    } finally {
        $harness->database->remove();
    }
});
