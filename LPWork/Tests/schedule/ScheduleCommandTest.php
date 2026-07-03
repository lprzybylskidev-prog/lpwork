<?php

declare(strict_types=1);

use LPWork\Console\CommandRegistry;
use LPWork\Console\Contracts\ProductionSensitiveCommand;
use LPWork\Console\Input;
use LPWork\Console\Middleware\ProductionSafetyMiddleware;
use LPWork\Console\Output;
use LPWork\Schedule\Commands\ScheduleListCommand;
use LPWork\Schedule\Commands\SchedulePruneCommand;
use LPWork\Schedule\Commands\ScheduleRunCommand;
use LPWork\Schedule\ScheduleListRenderer;
use LPWork\Schedule\SchedulePruner;
use LPWork\Schedule\ScheduleStoreFactory;
use Tests\support\console\OutputStreams;
use Tests\support\schedule\FailingCommand;
use Tests\support\schedule\RecordingCommand;
use Tests\support\schedule\ScheduleDatabaseHarness;

it('lists scheduled tasks', function (): void {
    $harness = ScheduleDatabaseHarness::create();
    $streams = OutputStreams::create();

    try {
        $harness->schedule->command('test:record', name: 'record')->hourly();
        $command = new ScheduleListCommand(
            $harness->schedule,
            new ScheduleListRenderer(new \LPWork\Console\ConsoleTableRenderer()),
        );

        $exitCode = $command->handle(new Input(['lpwork', 'schedule:list']), new Output($streams->stdout, $streams->stderr, decorated: false));

        expect($exitCode)->toBe(0)
            ->and($streams->stdout())->toContain('record')
            ->and($streams->stdout())->toContain('0 * * * *');
    } finally {
        $harness->database->remove();
    }
});

it('runs due scheduled commands and records history', function (): void {
    $registry = new CommandRegistry();
    $harness = ScheduleDatabaseHarness::create($registry);
    $path = $harness->database->basePath() . '/schedule.log';
    $streams = OutputStreams::create();

    try {
        $registry->add(new RecordingCommand($path));
        $harness->schedule->command('test:record', ['alpha'], name: 'record')->everyMinute();
        $command = new ScheduleRunCommand($harness->runner);

        $exitCode = $command->handle(new Input(['lpwork', 'schedule:run']), new Output($streams->stdout, $streams->stderr, decorated: false));
        $runs = $harness->store->runs();
        $firstRun = $runs[0] ?? null;

        expect($exitCode)->toBe(0)
            ->and(file_get_contents($path))->toBe("alpha\n")
            ->and($streams->stdout())->toContain('OK Schedule run complete.')
            ->and($streams->stdout())->toContain('| Due     | 1')
            ->and($streams->stdout())->toContain('| Ran     | 1')
            ->and($runs)->toHaveCount(1)
            ->and($firstRun)->toBeArray();

        if (!is_array($firstRun)) {
            return;
        }

        expect($firstRun['status'])->toBe('succeeded');
    } finally {
        $harness->database->remove();
    }
});

it('dispatches scheduled jobs through the queue boundary', function (): void {
    $harness = ScheduleDatabaseHarness::create();
    $path = $harness->database->basePath() . '/job.log';
    $streams = OutputStreams::create();

    try {
        $harness->schedule->job(new \Tests\support\schedule\RecordingJob($path, 'queued'), 'job')->everyMinute();
        $exitCode = new ScheduleRunCommand($harness->runner)->handle(
            new Input(['lpwork', 'schedule:run']),
            new Output($streams->stdout, $streams->stderr, decorated: false),
        );

        expect($exitCode)->toBe(0)
            ->and(file_get_contents($path))->toBe("queued\n");
    } finally {
        $harness->database->remove();
    }
});

it('skips scheduled tasks that already hold a shared atomic lock', function (): void {
    $registry = new CommandRegistry();
    $harness = ScheduleDatabaseHarness::create($registry);
    $path = $harness->database->basePath() . '/schedule.log';
    $streams = OutputStreams::create();

    try {
        $registry->add(new RecordingCommand($path));
        $task = $harness->schedule->command('test:record', ['alpha'], name: 'record')->everyMinute()->task();
        $lock = $harness->locks->lock($task->lockName(), 60);

        $lock->acquire();

        $exitCode = new ScheduleRunCommand($harness->runner)->handle(
            new Input(['lpwork', 'schedule:run']),
            new Output($streams->stdout, $streams->stderr, decorated: false),
        );
        $runs = $harness->store->runs();
        $firstRun = $runs[0] ?? null;

        expect($exitCode)->toBe(0)
            ->and(file_exists($path))->toBeFalse()
            ->and($streams->stdout())->toContain('| Skipped | 1')
            ->and($firstRun)->toBeArray();

        if (is_array($firstRun)) {
            expect($firstRun['status'])->toBe('skipped');
        }
    } finally {
        $harness->database->remove();
    }
});

it('isolates scheduled command failures', function (): void {
    $registry = new CommandRegistry();
    $harness = ScheduleDatabaseHarness::create($registry);
    $path = $harness->database->basePath() . '/schedule.log';
    $streams = OutputStreams::create();

    try {
        $registry->add(new FailingCommand());
        $registry->add(new RecordingCommand($path));
        $harness->schedule->command('test:fail', name: 'fail')->everyMinute();
        $harness->schedule->command('test:record', ['after'], name: 'after')->everyMinute();

        $exitCode = new ScheduleRunCommand($harness->runner)->handle(
            new Input(['lpwork', 'schedule:run']),
            new Output($streams->stdout, $streams->stderr, decorated: false),
        );

        expect($exitCode)->toBe(1)
            ->and(file_get_contents($path))->toBe("after\n")
            ->and($streams->stderr())->toContain('Failed [fail]')
            ->and($harness->store->runs())->toHaveCount(2);
    } finally {
        $harness->database->remove();
    }
});

it('prunes scheduler storage through a production-sensitive command', function (): void {
    $harness = ScheduleDatabaseHarness::create();
    $streams = OutputStreams::create();

    try {
        $harness->store->recordRun(
            $harness->schedule->command('test:record', name: 'record')->everyMinute()->task(),
            'succeeded',
            1,
            2,
            0,
            'done',
        );
        $harness->clock->travel(2);
        $stores = new ScheduleStoreFactory($harness->databaseManager, $harness->clock, 'sqlite', 'schedule_runs', true);
        $command = new SchedulePruneCommand(new SchedulePruner($stores, 1));
        $exitCode = $command->handle(new Input(['lpwork', 'schedule:prune']), new Output($streams->stdout, $streams->stderr, decorated: false));

        expect($exitCode)->toBe(0)
            ->and($streams->stdout())->toContain('OK Schedule pruned.')
            ->and($streams->stdout())->toContain('| Locks  | 0')
            ->and($streams->stdout())->toContain('| Runs   | 1')
            ->and($command)->toBeInstanceOf(ProductionSensitiveCommand::class)
            ->and($command->middleware())->toBe([ProductionSafetyMiddleware::class]);
    } finally {
        $harness->database->remove();
    }
});
