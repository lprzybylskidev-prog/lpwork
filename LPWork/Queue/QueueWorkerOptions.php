<?php

declare(strict_types=1);

namespace LPWork\Queue;

/**
 * Carries options for queue worker options behavior.
 */
final readonly class QueueWorkerOptions
{
    /**
     * Creates a new QueueWorkerOptions instance.
     */
    public function __construct(
        public string $connection,
        public string $queue,
        public bool $once = false,
        public int $maxJobs = 0,
        public int $sleepSeconds = 1,
        public ?int $retryAfterSeconds = null,
        public ?int $retryDelaySeconds = null,
    ) {}
}
