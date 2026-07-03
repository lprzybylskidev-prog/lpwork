<?php

declare(strict_types=1);

namespace LPWork\Queue\Drivers;

use LPWork\Queue\Contracts\QueueDriver;
use LPWork\Queue\DatabaseQueueRepository;
use LPWork\Queue\QueuedJobPayload;
use LPWork\Queue\ReservedJob;

/**
 * Represents the database queue driver framework component.
 */
final readonly class DatabaseQueueDriver implements QueueDriver
{
    /**
     * Creates a new DatabaseQueueDriver instance.
     */
    public function __construct(
        private DatabaseQueueRepository $repository,
    ) {}

    /**
     * Performs assert ready.
     */
    public function assertReady(): void
    {
        $this->repository->assertReady();
    }

    /**
     * Registers or stores push.
     */
    public function push(QueuedJobPayload $payload): string
    {
        $this->repository->push($payload);

        return $payload->id;
    }

    /**
     * Performs the reserve operation.
     */
    public function reserve(string $queue, int $retryAfterSeconds): ?ReservedJob
    {
        return $this->repository->reserve($queue, $retryAfterSeconds);
    }

    /**
     * Removes or clears release.
     */
    public function release(ReservedJob $job, int $delaySeconds): void
    {
        $this->repository->release($job, $delaySeconds);
    }

    /**
     * Performs the complete operation.
     */
    public function complete(ReservedJob $job): void
    {
        $this->repository->complete($job);
    }

    /**
     * Performs the fail operation.
     */
    public function fail(ReservedJob $job, string $exception): void
    {
        $this->repository->fail($job, $exception);
    }

    /**
     * Removes or clears prune completed.
     */
    public function pruneCompleted(int $olderThanSeconds): int
    {
        return $this->repository->pruneCompleted($olderThanSeconds);
    }

    /**
     * Removes or clears prune failed.
     */
    public function pruneFailed(int $olderThanSeconds): int
    {
        return $this->repository->pruneFailed($olderThanSeconds);
    }

    /**
     * Clears the state owned by this component.
     */
    public function clear(string $queue): int
    {
        return $this->repository->clear($queue);
    }
}
