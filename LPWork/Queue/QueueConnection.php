<?php

declare(strict_types=1);

namespace LPWork\Queue;

use LPWork\Queue\Contracts\QueueDriver;

/**
 * Represents the queue connection framework component.
 */
final readonly class QueueConnection
{
    /**
     * Creates a new QueueConnection instance.
     */
    public function __construct(
        public string $name,
        private QueueDriver $driver,
    ) {}

    /**
     * Performs assert ready.
     */
    public function assertReady(): void
    {
        $this->driver->assertReady();
    }

    /**
     * Registers or stores push.
     */
    public function push(QueuedJobPayload $payload): string
    {
        return $this->driver->push($payload);
    }

    /**
     * Performs the reserve operation.
     */
    public function reserve(string $queue, int $retryAfterSeconds): ?ReservedJob
    {
        return $this->driver->reserve($queue, $retryAfterSeconds);
    }

    /**
     * Removes or clears release.
     */
    public function release(ReservedJob $job, int $delaySeconds): void
    {
        $this->driver->release($job, $delaySeconds);
    }

    /**
     * Performs the complete operation.
     */
    public function complete(ReservedJob $job): void
    {
        $this->driver->complete($job);
    }

    /**
     * Performs the fail operation.
     */
    public function fail(ReservedJob $job, string $exception): void
    {
        $this->driver->fail($job, $exception);
    }

    /**
     * Removes or clears prune completed.
     */
    public function pruneCompleted(int $olderThanSeconds): int
    {
        return $this->driver->pruneCompleted($olderThanSeconds);
    }

    /**
     * Removes or clears prune failed.
     */
    public function pruneFailed(int $olderThanSeconds): int
    {
        return $this->driver->pruneFailed($olderThanSeconds);
    }

    /**
     * Clears the state owned by this component.
     */
    public function clear(string $queue): int
    {
        return $this->driver->clear($queue);
    }
}
