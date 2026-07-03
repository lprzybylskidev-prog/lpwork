<?php

declare(strict_types=1);

namespace LPWork\Schedule;

use LPWork\Schedule\Contracts\ScheduledTaskExecutor;

/**
 * Stores and resolves scheduled task executor registry registrations.
 */
final class ScheduledTaskExecutorRegistry
{
    /**
     * @var list<ScheduledTaskExecutor>
     */
    private array $executors = [];

    /**
     * Adds an item to this component's registry or backing store.
     */
    public function add(ScheduledTaskExecutor $executor): void
    {
        $this->executors[] = $executor;
    }

    /**
     * Performs the for task operation.
     */
    public function forTask(ScheduledTask $task): ScheduledTaskExecutor
    {
        foreach ($this->executors as $executor) {
            if ($executor->supports($task)) {
                return $executor;
            }
        }

        throw new Exceptions\InvalidScheduledTaskException(sprintf('No executor supports scheduled task [%s].', $task->name));
    }
}
