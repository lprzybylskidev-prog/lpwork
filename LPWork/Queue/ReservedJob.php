<?php

declare(strict_types=1);

namespace LPWork\Queue;

/**
 * Represents the reserved job framework component.
 */
final readonly class ReservedJob
{
    /**
     * Creates a new ReservedJob instance.
     */
    public function __construct(
        public QueuedJobPayload $payload,
        public int $attempts,
        public string $driverId,
    ) {}
}
