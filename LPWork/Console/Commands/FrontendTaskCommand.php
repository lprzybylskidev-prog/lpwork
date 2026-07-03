<?php

declare(strict_types=1);

namespace LPWork\Console\Commands;

use LPWork\Console\Contracts\Command;
use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Frontend\FrontendTask;
use LPWork\Frontend\FrontendTaskRunner;

/**
 * Handles the frontend task command console command.
 */
final readonly class FrontendTaskCommand implements Command
{
    /**
     * Creates a new FrontendTaskCommand instance.
     */
    public function __construct(
        private string $name,
        private string $description,
        private FrontendTask $task,
        private FrontendTaskRunner $runner,
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
