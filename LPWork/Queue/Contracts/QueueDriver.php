<?php

declare(strict_types=1);

namespace LPWork\Queue\Contracts;

use LPWork\Queue\QueuedJobPayload;
use LPWork\Queue\ReservedJob;

/**
 * Defines the contract for queue driver.
 */
interface QueueDriver
{
    /**
     * Performs assert ready.
     */
    public function assertReady(): void;

    /**
     * Registers or stores push.
     */
    public function push(QueuedJobPayload $payload): string;

    /**
     * Performs the reserve operation.
     */
    public function reserve(string $queue, int $retryAfterSeconds): ?ReservedJob;

    /**
     * Removes or clears release.
     */
    public function release(ReservedJob $job, int $delaySeconds): void;

    /**
     * Performs the complete operation.
     */
    public function complete(ReservedJob $job): void;

    /**
     * Performs the fail operation.
     */
    public function fail(ReservedJob $job, string $exception): void;

    /**
     * Removes or clears prune completed.
     */
    public function pruneCompleted(int $olderThanSeconds): int;

    /**
     * Removes or clears prune failed.
     */
    public function pruneFailed(int $olderThanSeconds): int;

    /**
     * Clears the state owned by this component.
     */
    public function clear(string $queue): int;
}
