<?php

declare(strict_types=1);

namespace LPWork\Queue\Events;

/**
 * Represents the queue job queued framework component.
 */
final readonly class QueueJobQueued
{
    /**
     * Creates a new QueueJobQueued instance.
     */
    public function __construct(
        public string $connection,
        public string $queue,
        public string $jobClass,
        public string $id,
    ) {}
}
