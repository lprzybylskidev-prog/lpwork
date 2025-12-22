<?php
declare(strict_types=1);

namespace LPwork\Queue\Contract;

use LPwork\Queue\QueueJob;

/**
 * Generic queue driver abstraction.
 */
interface QueueDriverInterface
{
    /**
     * Enqueue a job.
     *
     * @param QueueJob $job
     *
     * @return void
     */
    public function push(QueueJob $job): void;

    /**
     * Pop next available job, optionally blocking for a limited time.
     *
     * @param int $timeoutSeconds
     *
     * @return QueueJob|null
     */
    public function pop(int $timeoutSeconds): ?QueueJob;

    /**
     * Acknowledge successful processing.
     *
     * @param QueueJob $job
     *
     * @return void
     */
    public function ack(QueueJob $job): void;

    /**
     * Reject job, optionally requeue.
     *
     * @param QueueJob $job
     * @param bool     $requeue
     *
     * @return void
     */
    public function reject(QueueJob $job, bool $requeue): void;

    /**
     * Purge all pending jobs from the queue.
     *
     * @return void
     */
    public function purge(): void;
}
