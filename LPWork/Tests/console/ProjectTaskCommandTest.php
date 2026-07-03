<?php

declare(strict_types=1);

use LPWork\Console\Commands\ProjectTaskCommand;
use LPWork\Console\Contracts\ProcessRunner;
use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Console\ProcessCommand;
use LPWork\Console\ProjectTasks\ProjectFileFinder;
use LPWork\Console\ProjectTasks\ProjectTask;
use LPWork\Console\ProjectTasks\ProjectTaskFilter;
use LPWork\Console\ProjectTasks\ProjectTaskRunner;
use LPWork\Console\ProjectTasks\ProjectTaskScope;
use LPWork\Filesystem\Filesystem;
use LPWork\Frontend\BrowserTaskRunner;
use LPWork\Frontend\FrontendPackageManagerDetector;
use LPWork\Frontend\FrontendProcessFactory;
use LPWork\Frontend\FrontendTaskRunner;
use Tests\support\console\OutputStreams;

it('runs project quality tasks through the console process boundary', function (): void {
    $basePath = \Tests\support\ProjectPaths::root() . '/storage/testing/project-task-command';
    $files = new Filesystem();
    $files->write($basePath . '/LPWork/Test.php', '<?php');
    $files->write($basePath . '/App/Test.php', '<?php');
    $files->write($basePath . '/App/Modules/Billing/tests/backend/BillingModuleTest.php', '<?php');
    $files->write($basePath . '/LPWork/Tests/Test.php', '<?php');
    $files->write($basePath . '/public/index.php', '<?php');
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

    $runner = new ProjectTaskRunner(
        $basePath,
        new ProjectFileFinder($basePath),
        $processes,
        new FrontendTaskRunner(
            $basePath,
            new FrontendProcessFactory($basePath, new FrontendPackageManagerDetector($basePath, $files)),
            $processes,
            $files,
        ),
    );
    $streams = OutputStreams::create();
    $output = new Output($streams->stdout, $streams->stderr, decorated: false);

    try {
        expect($runner->run(ProjectTask::Format, $output))->toBe(0)
            ->and($runner->run(ProjectTask::Check, $output))->toBe(0)
            ->and($runner->run(ProjectTask::Test, $output))->toBe(0)
            ->and($runner->run(ProjectTask::TestLpwork, $output))->toBe(0)
            ->and($processes->commands)->toHaveCount(7)
            ->and($processes->commands[0]->command())->toBe([
                $basePath . '/vendor/bin/php-cs-fixer',
                'fix',
                '--config=.php-cs-fixer.dist.php',
                '--allow-risky=yes',
                '--path-mode=override',
                'LPWork',
                'App',
                'public',
            ])
            ->and($processes->commands[1]->command())->toBe([
                'npm',
                'run',
                'frontend:format',
            ])
            ->and($processes->commands[2]->command())->toBe([
                $basePath . '/vendor/bin/phpstan',
                'analyse',
                '--configuration=phpstan.neon',
                '--memory-limit=1G',
                'LPWork',
                'App',
                'public',
            ])
            ->and($processes->commands[3]->command())->toBe([
                'npm',
                'run',
                'frontend:check',
            ])
            ->and($processes->commands[4]->command())->toBe([
                $basePath . '/vendor/bin/pest',
                '--test-directory=App/Modules',
                'App/Modules/Billing/tests/backend',
            ])
            ->and($processes->commands[5]->command())->toBe([
                'npm',
                'run',
                'frontend:test',
            ])
            ->and($processes->commands[6]->command())->toBe([
                $basePath . '/vendor/bin/pest',
                '--test-directory=LPWork/Tests',
                'LPWork/Tests/Test.php',
            ])
            ->and($streams->stderr())->toBe('');
    } finally {
        $files->clearDirectory($basePath);
        @rmdir($basePath);
    }
});

it('runs framework browser tests only when requested on the framework test command', function (): void {
    $basePath = \Tests\support\ProjectPaths::root() . '/storage/testing/project-task-command-framework-browser';
    $files = new Filesystem();
    $files->write($basePath . '/LPWork/Tests/Test.php', '<?php');
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
    $frontendProcesses = new FrontendProcessFactory($basePath, new FrontendPackageManagerDetector($basePath, $files));
    $runner = new ProjectTaskRunner(
        $basePath,
        new ProjectFileFinder($basePath),
        $processes,
        new FrontendTaskRunner($basePath, $frontendProcesses, $processes, $files),
        new BrowserTaskRunner($frontendProcesses, $processes),
    );
    $command = new ProjectTaskCommand('test:lpwork', 'Run framework tests.', ProjectTask::TestLpwork, $runner);
    $streams = OutputStreams::create();

    try {
        expect($command->handle(new Input(['lpwork', 'test:lpwork', '--browser']), new Output($streams->stdout, $streams->stderr, decorated: false)))->toBe(0)
            ->and($processes->commands)->toHaveCount(2)
            ->and($processes->commands[0]->command())->toBe([
                $basePath . '/vendor/bin/pest',
                '--test-directory=LPWork/Tests',
                'LPWork/Tests/Test.php',
            ])
            ->and($processes->commands[1]->command())->toBe([
                'npm',
                'run',
                'browser:test',
            ])
            ->and($command->options())->toHaveCount(1)
            ->and($command->options()[0]->name())->toBe('browser')
            ->and($streams->stderr())->toBe('');
    } finally {
        $files->clearDirectory($basePath);
        @rmdir($basePath);
    }
});

it('reports missing coverage support before running coverage', function (): void {
    $basePath = \Tests\support\ProjectPaths::root();
    $processes = new class implements ProcessRunner {
        public function run(ProcessCommand $command, Output $output): int
        {
            $output->writeln('process ran');

            return 0;
        }
    };

    $runner = new ProjectTaskRunner(
        $basePath,
        new ProjectFileFinder($basePath),
        $processes,
        new FrontendTaskRunner(
            $basePath,
            new FrontendProcessFactory($basePath, new FrontendPackageManagerDetector($basePath)),
            $processes,
            new Filesystem(),
        ),
    );
    $streams = OutputStreams::create();

    $exitCode = $runner->run(ProjectTask::Coverage, new Output($streams->stdout, $streams->stderr, decorated: false));

    if (extension_loaded('xdebug') || extension_loaded('pcov')) {
        expect($exitCode)->toBe(0)
            ->and($streams->stdout())->toContain('process ran');

        return;
    }

    expect($exitCode)->toBe(1)
        ->and($streams->stderr())->toBe("No coverage driver found; enable Xdebug or PCOV to run Pest coverage.\n");
});

it('runs a project task command through the shared runner', function (): void {
    $basePath = \Tests\support\ProjectPaths::root();
    $processes = new class implements ProcessRunner {
        public function run(ProcessCommand $command, Output $output): int
        {
            $output->writeln('delegated');

            return 7;
        }
    };
    $runner = new ProjectTaskRunner(
        $basePath,
        new ProjectFileFinder($basePath),
        $processes,
        new FrontendTaskRunner(
            $basePath,
            new FrontendProcessFactory($basePath, new FrontendPackageManagerDetector($basePath)),
            $processes,
            new Filesystem(),
        ),
    );
    $command = new ProjectTaskCommand('test:fake', 'Fake task.', ProjectTask::Test, $runner);
    $streams = OutputStreams::create();

    expect($command->name())->toBe('test:fake')
        ->and($command->description())->toBe('Fake task.')
        ->and($command->handle(new Input(['lpwork', 'test:fake']), new Output($streams->stdout, $streams->stderr, decorated: false)))->toBe(7)
        ->and($streams->stdout())->toBe("delegated\n");
});

it('limits project task commands to backend or frontend scope', function (): void {
    $basePath = \Tests\support\ProjectPaths::root() . '/storage/testing/project-task-command-scope';
    $files = new Filesystem();
    $files->write($basePath . '/App/Modules/Billing/tests/backend/BillingModuleTest.php', '<?php');
    $files->write($basePath . '/App/Modules/Shop/tests/backend/ShopModuleTest.php', '<?php');
    $processes = new class implements ProcessRunner {
        /**
         * @var list<ProcessCommand>
         */
        public array $commands = [];

        public function run(ProcessCommand $command, Output $output): int
        {
            $this->commands[] = $command;

            return 0;
        }
    };
    $runner = new ProjectTaskRunner(
        $basePath,
        new ProjectFileFinder($basePath),
        $processes,
        new FrontendTaskRunner(
            $basePath,
            new FrontendProcessFactory($basePath, new FrontendPackageManagerDetector($basePath, $files)),
            $processes,
            $files,
        ),
    );
    $command = new ProjectTaskCommand('test', 'Run tests.', ProjectTask::Test, $runner);
    $streams = OutputStreams::create();

    try {
        expect($command->handle(new Input(['lpwork', 'test', '--frontend']), new Output($streams->stdout, $streams->stderr, decorated: false)))->toBe(0)
            ->and($processes->commands)->toHaveCount(1);

        $frontendCommand = $processes->commands[0] ?? null;

        if (!$frontendCommand instanceof ProcessCommand) {
            throw new RuntimeException('Expected the frontend task command to run one process.');
        }

        expect($frontendCommand->command())->toBe(['npm', 'run', 'frontend:test']);

        $backendProcesses = new class implements ProcessRunner {
            /**
             * @var list<ProcessCommand>
             */
            public array $commands = [];

            public function run(ProcessCommand $command, Output $output): int
            {
                $this->commands[] = $command;

                return 0;
            }
        };
        $backendRunner = new ProjectTaskRunner(
            $basePath,
            new ProjectFileFinder($basePath),
            $backendProcesses,
            new FrontendTaskRunner(
                $basePath,
                new FrontendProcessFactory($basePath, new FrontendPackageManagerDetector($basePath, $files)),
                $backendProcesses,
                $files,
            ),
        );
        $backendCommandRunner = new ProjectTaskCommand('test', 'Run tests.', ProjectTask::Test, $backendRunner);

        expect($backendCommandRunner->handle(new Input(['lpwork', 'test', '--backend']), new Output($streams->stdout, $streams->stderr, decorated: false)))->toBe(0)
            ->and($backendProcesses->commands)->toHaveCount(1);

        $backendCommand = $backendProcesses->commands[0] ?? null;

        if (!$backendCommand instanceof ProcessCommand) {
            throw new RuntimeException('Expected the backend task command to run one process.');
        }

        expect($backendCommand->command())->toBe([
            $basePath . '/vendor/bin/pest',
            '--test-directory=App/Modules',
            'App/Modules/Billing/tests/backend',
            'App/Modules/Shop/tests/backend',
        ])
            ->and($backendCommandRunner->options())->toHaveCount(3);

        $moduleProcesses = new class implements ProcessRunner {
            /**
             * @var list<ProcessCommand>
             */
            public array $commands = [];

            public function run(ProcessCommand $command, Output $output): int
            {
                $this->commands[] = $command;

                return 0;
            }
        };
        $moduleRunner = new ProjectTaskRunner(
            $basePath,
            new ProjectFileFinder($basePath),
            $moduleProcesses,
            new FrontendTaskRunner(
                $basePath,
                new FrontendProcessFactory($basePath, new FrontendPackageManagerDetector($basePath, $files)),
                $moduleProcesses,
                $files,
            ),
        );

        expect($moduleRunner->run(ProjectTask::Test, new Output($streams->stdout, $streams->stderr, decorated: false), new ProjectTaskFilter(ProjectTaskScope::Backend, 'Billing')))->toBe(0)
            ->and($moduleProcesses->commands)->toHaveCount(1)
            ->and($moduleProcesses->commands[0]->command())->toBe([
                $basePath . '/vendor/bin/pest',
                '--test-directory=App/Modules',
                'App/Modules/Billing/tests/backend',
            ]);
    } finally {
        $files->clearDirectory($basePath);
        @rmdir($basePath);
    }
});
