<?php
declare(strict_types=1);

namespace LPwork\Queue;

use Carbon\CarbonImmutable;
use LPwork\Queue\Contract\QueueDriverInterface;

/**
 * Dispatches jobs to configured queues.
 */
class QueueDispatcher
{
    /**
     * @var QueueManager
     */
    private QueueManager $queues;

    /**
     * @param QueueManager $queues
     */
    public function __construct(QueueManager $queues)
    {
        $this->queues = $queues;
    }

    /**
     * Enqueue a job payload to a queue.
     *
     * @param array<string, mixed> $payload
     * @param string|null          $queue
     * @param int|null             $maxAttempts
     *
     * @return string generated job id
     */
    public function dispatch(
        array $payload,
        ?string $queue = null,
        ?int $maxAttempts = null,
    ): string {
        $queueName = $queue ?? $this->queues->defaultQueue();
        $jobId = \bin2hex(\random_bytes(16));
        $job = new QueueJob(
            $jobId,
            $queueName,
            $payload,
            attempts: 0,
            maxAttempts: $maxAttempts,
            availableAt: new CarbonImmutable(),
        );

        $driver = $this->driver($queue);
        $driver->push($job);

        return $jobId;
    }

    /**
     * @param string|null $queue
     *
     * @return QueueDriverInterface
     */
    private function driver(?string $queue): QueueDriverInterface
    {
        return $this->queues->queue($queue);
    }
}
