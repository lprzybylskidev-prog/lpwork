<?php

declare(strict_types=1);

namespace LPWork\Console\Commands;

use LPWork\Console\Contracts\Command;
use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Frontend\BrowserTask;
use LPWork\Frontend\BrowserTaskRunner;

/**
 * Handles the browser task command console command.
 */
final readonly class BrowserTaskCommand implements Command
{
    /**
     * Creates a new BrowserTaskCommand instance.
     */
    public function __construct(
        private string $name,
        private string $description,
        private BrowserTask $task,
        private BrowserTaskRunner $runner,
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return $this->description;
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Input $input, Output $output): int
    {
        return $this->runner->run($this->task, $output);
    }
}
