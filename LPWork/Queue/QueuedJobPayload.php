<?php

declare(strict_types=1);

namespace LPWork\Queue;

/**
 * Represents the queued job payload framework component.
 */
final readonly class QueuedJobPayload
{
    /**
     * Creates a new QueuedJobPayload instance.
     */
    public function __construct(
        public string $id,
        public string $queue,
        public string $jobClass,
        public string $body,
        public int $maxAttempts,
        public int $availableAt,
        public int $createdAt,
    ) {}
}
