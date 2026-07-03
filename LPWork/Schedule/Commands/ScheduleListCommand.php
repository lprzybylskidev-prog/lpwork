<?php

declare(strict_types=1);

namespace LPWork\Schedule\Commands;

use LPWork\Console\Contracts\Command;
use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Schedule\ScheduleListRenderer;
use LPWork\Schedule\ScheduleRegistry;

/**
 * Handles the schedule list command console command.
 */
final readonly class ScheduleListCommand implements Command
{
    /**
     * Creates a new ScheduleListCommand instance.
     */
    public function __construct(
        private ScheduleRegistry $schedule,
        private ScheduleListRenderer $renderer,
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'schedule:list';
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return 'List scheduled tasks.';
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Input $input, Output $output): int
    {
        $this->renderer->render($this->schedule->all(), $output);

        return 0;
    }
}
