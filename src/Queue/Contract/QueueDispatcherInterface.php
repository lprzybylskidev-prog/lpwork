<?php
declare(strict_types=1);

namespace LPwork\Queue\Contract;

/**
 * Dispatches jobs to configured queues.
 */
interface QueueDispatcherInterface
{
    /**
     * Enqueue a job payload to a queue.
     *
     * @param array<string, mixed> $payload
     * @param string|null          $queue
     * @param int|null             $maxAttempts
     *
     * @return string
     */
    public function dispatch(
        array $payload,
        ?string $queue = null,
        ?int $maxAttempts = null,
    ): string;
}
