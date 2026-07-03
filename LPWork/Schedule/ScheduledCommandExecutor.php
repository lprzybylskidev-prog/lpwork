<?php

declare(strict_types=1);

namespace LPWork\Schedule;

use LPWork\Console\CommandRegistry;
use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Schedule\Contracts\ScheduledTaskExecutor;
use LPWork\Schedule\Exceptions\InvalidScheduledTaskException;

/**
 * Represents the scheduled command executor framework component.
 */
final readonly class ScheduledCommandExecutor implements ScheduledTaskExecutor
{
    /**
     * Creates a new ScheduledCommandExecutor instance.
     */
    public function __construct(
        private CommandRegistry $commands,
    ) {}

    /**
     * Reports whether supports.
     */
    public function supports(ScheduledTask $task): bool
    {
        return $task->type === ScheduledTaskType::Command;
    }

    /**
     * Runs execute.
     */
    public function execute(ScheduledTask $task, Output $output): ScheduledTaskResult
    {
        if (!$this->commands->has($task->target)) {
            throw InvalidScheduledTaskException::commandNotRegistered($task->target);
        }

        $argv = ['lpwork', $task->target, ...$task->arguments, ...$this->optionTokens($task->options)];
        $exitCode = $this->commands->get($task->target)->handle(new Input($argv), $output);

        return new ScheduledTaskResult($exitCode, sprintf('Command [%s] exited with code %d.', $task->target, $exitCode));
    }

    /**
     * @param array<string, string|bool|int> $options
     * @return list<string>
     */
    private function optionTokens(array $options): array
    {
        $tokens = [];

        foreach ($options as $name => $value) {
            if ($value === true) {
                $tokens[] = '--' . $name;

                continue;
            }

            if ($value === false) {
                $tokens[] = '--no-' . $name;

                continue;
            }

            $tokens[] = sprintf('--%s=%s', $name, $value);
        }

        return $tokens;
    }
}
