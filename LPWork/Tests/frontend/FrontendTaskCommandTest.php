<?php

declare(strict_types=1);

use LPWork\Console\Commands\FrontendTaskCommand;
use LPWork\Console\Contracts\ProcessRunner;
use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Console\ProcessCommand;
use LPWork\Filesystem\Filesystem;
use LPWork\Frontend\FrontendPackageManagerDetector;
use LPWork\Frontend\FrontendProcessFactory;
use LPWork\Frontend\FrontendTask;
use LPWork\Frontend\FrontendTaskRunner;
use Tests\support\console\OutputStreams;
use Tests\support\frontend\FrontendPackageManagerTestWorkspace;

afterAll(function (): void {
    FrontendPackageManagerTestWorkspace::clear();
});

it('runs frontend process tasks through the detected package manager', function (): void {
    $workspace = FrontendPackageManagerTestWorkspace::create();
    $workspace->writeLockfile('pnpm-lock.yaml');
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
    $runner = new FrontendTaskRunner(
        $workspace->basePath(),
        new FrontendProcessFactory(
            $workspace->basePath(),
            new FrontendPackageManagerDetector($workspace->basePath(), $workspace->files()),
        ),
        $processes,
        $workspace->files(),
    );
    $streams = OutputStreams::create();
    $output = new Output($streams->stdout, $streams->stderr, decorated: false);

    expect($runner->run(FrontendTask::Install, $output))->toBe(0)
        ->and($runner->run(FrontendTask::Dev, $output))->toBe(0)
        ->and($runner->run(FrontendTask::Build, $output))->toBe(0)
        ->and($runner->run(FrontendTask::Format, $output))->toBe(0)
        ->and($runner->run(FrontendTask::Check, $output))->toBe(0)
        ->and($runner->run(FrontendTask::Test, $output))->toBe(0)
        ->and($processes->commands)->toHaveCount(6)
        ->and($processes->commands[0]->command())->toBe(['pnpm', 'install'])
        ->and($processes->commands[1]->command())->toBe(['pnpm', 'run', 'frontend:dev'])
        ->and($processes->commands[2]->command())->toBe(['pnpm', 'run', 'frontend:build'])
        ->and($processes->commands[3]->command())->toBe(['pnpm', 'run', 'frontend:format'])
        ->and($processes->commands[4]->command())->toBe(['pnpm', 'run', 'frontend:check'])
        ->and($processes->commands[5]->command())->toBe(['pnpm', 'run', 'frontend:test'])
        ->and($processes->commands[5]->workingDirectory())->toBe($workspace->basePath());
});

it('cleans generated frontend artifacts without running a package manager process', function (): void {
    $workspace = FrontendPackageManagerTestWorkspace::create();
    $workspace->files()->write($workspace->basePath() . '/public/build/app.js', 'build');
    $workspace->files()->write($workspace->basePath() . '/node_modules/.vite/cache.js', 'cache');
    $processes = new class implements ProcessRunner {
        public int $runs = 0;

        public function run(ProcessCommand $command, Output $output): int
        {
            $this->runs++;

            return 0;
        }
    };
    $runner = new FrontendTaskRunner(
        $workspace->basePath(),
        new FrontendProcessFactory(
            $workspace->basePath(),
            new FrontendPackageManagerDetector($workspace->basePath(), $workspace->files()),
        ),
        $processes,
        $workspace->files(),
    );
    $streams = OutputStreams::create();

    expect($runner->run(FrontendTask::Clean, new Output($streams->stdout, $streams->stderr, decorated: false)))->toBe(0)
        ->and($workspace->files()->isFile($workspace->basePath() . '/public/build/app.js'))->toBeFalse()
        ->and($workspace->files()->isFile($workspace->basePath() . '/node_modules/.vite/cache.js'))->toBeFalse()
        ->and($processes->runs)->toBe(0)
        ->and($streams->stdout())->toContain('Frontend artifacts cleaned: public/build, node_modules/.vite.');
});

it('runs a frontend task command through the shared runner', function (): void {
    $runner = new FrontendTaskRunner(
        \Tests\support\ProjectPaths::root(),
        new FrontendProcessFactory(
            \Tests\support\ProjectPaths::root(),
            new FrontendPackageManagerDetector(\Tests\support\ProjectPaths::root()),
        ),
        new class implements ProcessRunner {
            public function run(ProcessCommand $command, Output $output): int
            {
                $output->writeln(implode(' ', $command->command()));

                return 7;
            }
        },
        new Filesystem(),
    );
    $command = new FrontendTaskCommand('frontend:fake', 'Fake frontend task.', FrontendTask::Check, $runner);
    $streams = OutputStreams::create();

    expect($command->name())->toBe('frontend:fake')
        ->and($command->description())->toBe('Fake frontend task.')
        ->and($command->handle(new Input(['lpwork', 'frontend:fake']), new Output($streams->stdout, $streams->stderr, decorated: false)))->toBe(7)
        ->and($streams->stdout())->toContain('npm run frontend:check');
});
