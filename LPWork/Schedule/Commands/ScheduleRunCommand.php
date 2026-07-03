<?php

declare(strict_types=1);

namespace LPWork\Schedule\Commands;

use LPWork\Console\ConsoleMessageFormatter;
use LPWork\Console\ConsoleOption;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Contracts\DescribesInput;
use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Schedule\ScheduleRunner;
use LPWork\Schedule\ScheduleRunOptions;

/**
 * Handles the schedule run command console command.
 */
final readonly class ScheduleRunCommand implements Command, DescribesInput
{
    /**
     * Creates a new ScheduleRunCommand instance.
     */
    public function __construct(
        private ScheduleRunner $runner,
        private ConsoleMessageFormatter $messages = new ConsoleMessageFormatter(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'schedule:run';
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return 'Run due scheduled tasks once.';
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Input $input, Output $output): int
    {
        $task = $input->option('task');
        $result = $this->runner->run(new ScheduleRunOptions(
            task: is_string($task) && $task !== '' ? $task : null,
            force: $input->hasOption('force'),
        ), $output);

        if ($result->failed > 0) {
            $this->messages->warning($output, 'Schedule run complete with failed tasks.');
        } else {
            $this->messages->success($output, 'Schedule run complete.');
        }

        $this->messages->summary($output, [
            'Due' => $result->due,
            'Ran' => $result->ran,
            'Skipped' => $result->skipped,
            'Failed' => $result->failed,
        ]);

        return $result->failed > 0 ? 1 : 0;
    }

    /**
     * @return list<\LPWork\Console\ConsoleArgument>
     */
    public function arguments(): array
    {
        return [];
    }

    /**
     * @return list<ConsoleOption>
     */
    public function options(): array
    {
        return [
            ConsoleOption::value('task', description: 'Run one task by its schedule name.'),
            ConsoleOption::flag('force', description: 'Run scheduled tasks even when they are not due.'),
        ];
    }
}
