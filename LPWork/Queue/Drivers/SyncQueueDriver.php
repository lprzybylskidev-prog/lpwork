<?php

declare(strict_types=1);

namespace LPWork\Queue\Drivers;

use LPWork\Queue\Contracts\QueueDriver;
use LPWork\Queue\QueuedJobPayload;
use LPWork\Queue\QueueJobRunner;
use LPWork\Queue\ReservedJob;

/**
 * Represents the sync queue driver framework component.
 */
final readonly class SyncQueueDriver implements QueueDriver
{
    /**
     * Creates a new SyncQueueDriver instance.
     */
    public function __construct(
        private QueueJobRunner $runner,
    ) {}

    /**
     * Performs assert ready.
     */
    public function assertReady(): void {}

    /**
     * Registers or stores push.
     */
    public function push(QueuedJobPayload $payload): string
    {
        $this->runner->run(new ReservedJob($payload, attempts: 1, driverId: $payload->id));

        return $payload->id;
    }

    /**
     * Performs the reserve operation.
     */
    public function reserve(string $queue, int $retryAfterSeconds): ?ReservedJob
    {
        return null;
    }

    /**
     * Removes or clears release.
     */
    public function release(ReservedJob $job, int $delaySeconds): void {}

    /**
     * Performs the complete operation.
     */
    public function complete(ReservedJob $job): void {}

    /**
     * Performs the fail operation.
     */
    public function fail(ReservedJob $job, string $exception): void {}

    /**
     * Removes or clears prune completed.
     */
    public function pruneCompleted(int $olderThanSeconds): int
    {
        return 0;
    }

    /**
     * Removes or clears prune failed.
     */
    public function pruneFailed(int $olderThanSeconds): int
    {
        return 0;
    }

    /**
     * Clears the state owned by this component.
     */
    public function clear(string $queue): int
    {
        return 0;
    }
}
