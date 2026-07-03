<?php

declare(strict_types=1);

namespace LPWork\Schedule;

use LPWork\Console\ConsoleTable;
use LPWork\Console\ConsoleTableRenderer;
use LPWork\Console\Enums\ConsoleColor;
use LPWork\Console\Enums\ConsoleStyle;
use LPWork\Console\Output;

/**
 * Renders schedule list renderer output.
 */
final readonly class ScheduleListRenderer
{
    /**
     * Creates a new ScheduleListRenderer instance.
     */
    public function __construct(
        private ConsoleTableRenderer $tables,
    ) {}

    /**
     * @param list<ScheduledTask> $tasks
     */
    public function render(array $tasks, Output $output): void
    {
        if ($tasks === []) {
            $output->writelnFormatted('No scheduled tasks registered.', ConsoleColor::Gray);

            return;
        }

        $rows = array_map(static fn(ScheduledTask $task): array => [
            $task->name,
            $task->type->value,
            $task->target,
            $task->frequency->expression(),
            $task->withoutOverlapping ? 'yes' : 'no',
        ], $tasks);

        $output->writelnFormatted('Scheduled tasks:', ConsoleColor::Yellow, styles: [ConsoleStyle::Bold]);
        $this->tables->render(ConsoleTable::make(['Name', 'Type', 'Target', 'Expression', 'Lock'], $rows), $output);
    }
}
