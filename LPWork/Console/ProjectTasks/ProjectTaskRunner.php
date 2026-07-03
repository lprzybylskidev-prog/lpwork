<?php

declare(strict_types=1);

namespace LPWork\Console\ProjectTasks;

use DirectoryIterator;

use function extension_loaded;

use LPWork\Console\Contracts\ProcessRunner;
use LPWork\Console\Output;
use LPWork\Console\ProcessCommand;
use LPWork\Console\ProcessEnvironment;
use LPWork\Frontend\BrowserTask;
use LPWork\Frontend\BrowserTaskRunner;
use LPWork\Frontend\FrontendTask;
use LPWork\Frontend\FrontendTaskRunner;

/**
 * Represents the project task runner framework component.
 */
final readonly class ProjectTaskRunner
{
    private const array PHP_DIRECTORIES = ['LPWork', 'App', 'public'];

    /**
     * Creates a new ProjectTaskRunner instance.
     */
    public function __construct(
        private string $basePath,
        private ProjectFileFinder $files,
        private ProcessRunner $processes,
        private FrontendTaskRunner $frontend,
        private ?BrowserTaskRunner $browser = null,
        private ProcessEnvironment $environment = new ProcessEnvironment(),
    ) {}

    /**
     * Runs run.
     */
    public function run(ProjectTask $task, Output $output, ProjectTaskFilter $filter = new ProjectTaskFilter()): int
    {
        return match ($task) {
            ProjectTask::Format => $this->runScoped($filter, $output, $this->formatBackend(...), FrontendTask::Format),
            ProjectTask::Check => $this->runScoped($filter, $output, $this->checkBackend(...), FrontendTask::Check),
            ProjectTask::Test => $this->runScoped($filter, $output, $this->testBackend(...), FrontendTask::Test),
            ProjectTask::Coverage => $this->coverage($output),
            ProjectTask::TestLpwork => $this->testFramework($output, $filter),
        };
    }

    private function testFramework(Output $output, ProjectTaskFilter $filter): int
    {
        $exitCode = $this->testSuite($output, 'LPWork/Tests', 'No LPWork test files found; skipping Pest.');

        if ($exitCode !== 0 || !$filter->browser) {
            return $exitCode;
        }

        if (!$this->browser instanceof BrowserTaskRunner) {
            $output->error('Framework browser tests require a browser task runner.');

            return 1;
        }

        return $this->browser->run(BrowserTask::Test, $output);
    }

    private function formatBackend(Output $output, ProjectTaskFilter $filter): int
    {
        unset($filter);

        if (!$this->files->hasPhpFiles(self::PHP_DIRECTORIES)) {
            $output->writeln('No PHP files found; skipping PHP CS Fixer.');

            return 0;
        }

        return $this->processes->run(new ProcessCommand([
            $this->vendorBinary('php-cs-fixer'),
            'fix',
            '--config=.php-cs-fixer.dist.php',
            '--allow-risky=yes',
            '--path-mode=override',
            ...self::PHP_DIRECTORIES,
        ], $this->basePath), $output);
    }

    private function checkBackend(Output $output, ProjectTaskFilter $filter): int
    {
        unset($filter);

        if (!$this->files->hasPhpFiles(self::PHP_DIRECTORIES)) {
            $output->writeln('No PHP files found; skipping PHPStan.');

            return 0;
        }

        return $this->processes->run(new ProcessCommand([
            $this->vendorBinary('phpstan'),
            'analyse',
            '--configuration=phpstan.neon',
            '--memory-limit=1G',
            ...self::PHP_DIRECTORIES,
        ], $this->basePath), $output);
    }

    private function testBackend(Output $output, ProjectTaskFilter $filter): int
    {
        $directories = $this->moduleBackendTestDirectories($filter->module);

        if ($directories === []) {
            $output->writeln($filter->module === null
                ? 'No application module backend test files found; skipping Pest.'
                : sprintf('No backend test files found for application module [%s]; skipping Pest.', $filter->module));

            return 0;
        }

        return $this->processes->run(new ProcessCommand([
            $this->vendorBinary('pest'),
            '--test-directory=App/Modules',
            ...$directories,
        ], $this->basePath), $output);
    }

    /**
     * @param callable(Output, ProjectTaskFilter): int $backend
     */
    private function runScoped(ProjectTaskFilter $filter, Output $output, callable $backend, FrontendTask $frontend): int
    {
        if ($filter->scope->includesBackend()) {
            $exitCode = $backend($output, $filter);

            if ($exitCode !== 0) {
                return $exitCode;
            }
        }

        if ($filter->scope->includesFrontend()) {
            return $this->frontend->run($frontend, $output);
        }

        return 0;
    }

    private function coverage(Output $output): int
    {
        $tests = $this->files->testFiles(['LPWork/Tests', 'App/Modules']);

        if ($tests === []) {
            $output->writeln('No test files found; skipping Pest coverage.');

            return 0;
        }

        if (!extension_loaded('xdebug') && !extension_loaded('pcov')) {
            $output->error('No coverage driver found; enable Xdebug or PCOV to run Pest coverage.');

            return 1;
        }

        return $this->processes->run(new ProcessCommand([
            PHP_BINARY,
            '-d',
            'memory_limit=1G',
            $this->vendorBinary('pest'),
            '--test-directory=LPWork/Tests',
            ...$tests,
            '--coverage',
        ], $this->basePath, $this->coverageEnvironment()), $output);
    }

    private function testSuite(Output $output, string $directory, string $emptyMessage): int
    {
        $tests = $this->files->testFiles([$directory]);

        if ($tests === []) {
            $output->writeln($emptyMessage);

            return 0;
        }

        return $this->processes->run(new ProcessCommand([
            $this->vendorBinary('pest'),
            '--test-directory=' . $directory,
            ...$tests,
        ], $this->basePath), $output);
    }

    /**
     * @return list<string>
     */
    private function moduleBackendTestDirectories(?string $module): array
    {
        if ($module !== null) {
            return $this->files->hasPhpFiles([$this->moduleBackendTestDirectory($module)])
                ? [$this->moduleBackendTestDirectory($module)]
                : [];
        }

        $modulesPath = $this->basePath . '/App/Modules';

        if (!is_dir($modulesPath)) {
            return [];
        }

        $directories = [];

        foreach (new DirectoryIterator($modulesPath) as $entry) {
            if (!$entry->isDir() || $entry->isDot()) {
                continue;
            }

            $directory = 'App/Modules/' . $entry->getFilename() . '/tests/backend';

            if ($this->files->hasPhpFiles([$directory])) {
                $directories[] = $directory;
            }
        }

        sort($directories);

        return $directories;
    }

    private function moduleBackendTestDirectory(string $module): string
    {
        return 'App/Modules/' . trim($module, '/') . '/tests/backend';
    }

    private function vendorBinary(string $binary): string
    {
        return $this->basePath . '/vendor/bin/' . $binary;
    }

    /**
     * @return array<string, string>
     */
    private function coverageEnvironment(): array
    {
        $environment = $this->environment->all();
        $environment['XDEBUG_MODE'] = 'coverage';

        return $environment;
    }
}
