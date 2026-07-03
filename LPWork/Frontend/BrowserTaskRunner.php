<?php

declare(strict_types=1);

namespace LPWork\Frontend;

use LPWork\Console\Contracts\ProcessRunner;
use LPWork\Console\Output;

/**
 * Represents the browser task runner framework component.
 */
final readonly class BrowserTaskRunner
{
    /**
     * Creates a new BrowserTaskRunner instance.
     */
    public function __construct(
        private FrontendProcessFactory $processes,
        private ProcessRunner $runner,
    ) {}

    /**
     * Runs run.
     */
    public function run(BrowserTask $task, Output $output): int
    {
        return $this->runner->run($this->processes->runScript($task->script()), $output);
    }
}
