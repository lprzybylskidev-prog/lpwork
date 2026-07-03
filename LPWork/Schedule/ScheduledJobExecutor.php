<?php

declare(strict_types=1);

namespace LPWork\Schedule;

use LPWork\Console\Output;
use LPWork\Queue\QueueDispatchOptions;
use LPWork\Queue\QueueManager;
use LPWork\Schedule\Contracts\ScheduledTaskExecutor;
use LPWork\Schedule\Exceptions\InvalidScheduledTaskException;

/**
 * Represents the scheduled job executor framework component.
 */
final readonly class ScheduledJobExecutor implements ScheduledTaskExecutor
{
    /**
     * Creates a new ScheduledJobExecutor instance.
     */
    public function __construct(
        private QueueManager $queues,
    ) {}

    /**
     * Reports whether supports.
     */
    public function supports(ScheduledTask $task): bool
    {
        return $task->type === ScheduledTaskType::Job;
    }

    /**
     * Runs execute.
     */
    public function execute(ScheduledTask $task, Output $output): ScheduledTaskResult
    {
        if ($task->job === null || !method_exists($task->job, 'handle')) {
            throw InvalidScheduledTaskException::missingJobHandler($task->target);
        }

        $id = $this->queues->dispatch($task->job, new QueueDispatchOptions(
            connection: $task->queueConnection,
            queue: $task->queue,
            delaySeconds: $task->delaySeconds,
        ));

        return new ScheduledTaskResult(0, sprintf('Queued job [%s] as [%s].', $task->target, $id));
    }
}
