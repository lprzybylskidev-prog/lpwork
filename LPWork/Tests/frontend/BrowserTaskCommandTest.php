<?php

declare(strict_types=1);

use LPWork\Console\Commands\BrowserTaskCommand;
use LPWork\Console\Contracts\ProcessRunner;
use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Console\ProcessCommand;
use LPWork\Frontend\BrowserTask;
use LPWork\Frontend\BrowserTaskRunner;
use LPWork\Frontend\FrontendPackageManagerDetector;
use LPWork\Frontend\FrontendProcessFactory;
use Tests\support\console\OutputStreams;
use Tests\support\frontend\FrontendPackageManagerTestWorkspace;

afterAll(function (): void {
    FrontendPackageManagerTestWorkspace::clear();
});

it('runs browser tasks through the detected package manager without using frontend test tasks', function (): void {
    $workspace = FrontendPackageManagerTestWorkspace::create();
    $workspace->writeLockfile('bun.lock');
    $processes = new class implements ProcessRunner {
        /**
         * @var list<ProcessCommand>
         */
        public array $commands = [];

        public function run(ProcessCommand $command, Output $output): int
        {
            $this->commands[] = $command;
            $output->writeln('process ran');

            return 0;
        }
    };
    $runner = new BrowserTaskRunner(
        new FrontendProcessFactory(
            $workspace->basePath(),
            new FrontendPackageManagerDetector($workspace->basePath(), $workspace->files()),
        ),
        $processes,
    );
    $streams = OutputStreams::create();
    $output = new Output($streams->stdout, $streams->stderr, decorated: false);

    expect($runner->run(BrowserTask::Install, $output))->toBe(0)
        ->and($runner->run(BrowserTask::Test, $output))->toBe(0)
        ->and($runner->run(BrowserTask::Ui, $output))->toBe(0)
        ->and($processes->commands)->toHaveCount(3)
        ->and($processes->commands[0]->command())->toBe(['bun', 'run', 'browser:install'])
        ->and($processes->commands[1]->command())->toBe(['bun', 'run', 'browser:test'])
        ->and($processes->commands[2]->command())->toBe(['bun', 'run', 'browser:test:ui'])
        ->and($processes->commands[2]->workingDirectory())->toBe($workspace->basePath());
});

it('runs a browser task command through the shared runner', function (): void {
    $runner = new BrowserTaskRunner(
        new FrontendProcessFactory(
            \Tests\support\ProjectPaths::root(),
            new FrontendPackageManagerDetector(\Tests\support\ProjectPaths::root()),
        ),
        new class implements ProcessRunner {
            public function run(ProcessCommand $command, Output $output): int
            {
                $output->writeln(implode(' ', $command->command()));

                return 9;
            }
        },
    );
    $command = new BrowserTaskCommand('browser:fake', 'Fake browser task.', BrowserTask::Ui, $runner);
    $streams = OutputStreams::create();

    expect($command->name())->toBe('browser:fake')
        ->and($command->description())->toBe('Fake browser task.')
        ->and($command->handle(new Input(['lpwork', 'browser:fake']), new Output($streams->stdout, $streams->stderr, decorated: false)))->toBe(9)
        ->and($streams->stdout())->toContain('npm run browser:test:ui');
});
