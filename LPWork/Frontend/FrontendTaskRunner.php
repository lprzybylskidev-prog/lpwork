<?php

declare(strict_types=1);

namespace LPWork\Frontend;

use LPWork\Console\ConsoleMessageFormatter;
use LPWork\Console\Contracts\ProcessRunner;
use LPWork\Console\Output;
use LPWork\Filesystem\Filesystem;

/**
 * Represents the frontend task runner framework component.
 */
final readonly class FrontendTaskRunner
{
    private const array GENERATED_ARTIFACT_DIRECTORIES = [
        'public/build',
        'node_modules/.vite',
    ];

    /**
     * Creates a new FrontendTaskRunner instance.
     */
    public function __construct(
        private string $basePath,
        private FrontendProcessFactory $processes,
        private ProcessRunner $runner,
        private Filesystem $files,
        private ConsoleMessageFormatter $messages = new ConsoleMessageFormatter(),
    ) {}

    /**
     * Runs run.
     */
    public function run(FrontendTask $task, Output $output): int
    {
        if ($task === FrontendTask::Install) {
            return $this->runner->run($this->processes->install(), $output);
        }

        if ($task === FrontendTask::Clean) {
            return $this->clean($output);
        }

        $script = $task->script();

        if ($script === null) {
            return 1;
        }

        return $this->runner->run($this->processes->runScript($script), $output);
    }

    private function clean(Output $output): int
    {
        foreach (self::GENERATED_ARTIFACT_DIRECTORIES as $directory) {
            $this->files->clearDirectory($this->basePath . '/' . $directory);
        }

        $this->messages->success($output, 'Frontend artifacts cleaned: ' . implode(', ', self::GENERATED_ARTIFACT_DIRECTORIES) . '.');

        return 0;
    }
}
