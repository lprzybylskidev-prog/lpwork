<?php
declare(strict_types=1);

namespace LPwork\Queue;

use LPwork\Queue\Contract\QueueDispatcherInterface;
use LPwork\Queue\Contract\QueueManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Dispatches jobs to configured queues.
 */
class QueueDispatcher implements QueueDispatcherInterface
{
    /**
     * @var QueueManagerInterface
     */
    private QueueManagerInterface $queues;

    /**
     * @var MessageBusInterface
     */
    private MessageBusInterface $bus;

    /**
     * @param QueueManagerInterface $queues
     * @param MessageBusInterface $bus
     */
    public function __construct(QueueManagerInterface $queues, MessageBusInterface $bus)
    {
        $this->queues = $queues;
        $this->bus = $bus;
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
            availableAt: null,
        );

        $this->bus->dispatch($job);

        return $jobId;
    }
}
