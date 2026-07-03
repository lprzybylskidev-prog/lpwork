<?php

declare(strict_types=1);

namespace LPWork\Queue;

/**
 * Represents the queue pruner framework component.
 */
final readonly class QueuePruner
{
    /**
     * Creates a new QueuePruner instance.
     */
    public function __construct(
        private QueueManager $queues,
    ) {}

    /**
     * @return array{completed: int, failed: int}
     */
    public function prune(?string $connection = null): array
    {
        $queue = $connection === null ? $this->queues->default() : $this->queues->connection($connection);

        return [
            'completed' => $queue->pruneCompleted($this->queues->completedRetentionSeconds()),
            'failed' => $queue->pruneFailed($this->queues->failedRetentionSeconds()),
        ];
    }
}
