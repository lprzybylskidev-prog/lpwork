<?php

declare(strict_types=1);

namespace LPWork\Queue\Events;

use LPWork\Queue\ReservedJob;

/**
 * Represents the queue job processed framework component.
 */
final readonly class QueueJobProcessed
{
    /**
     * Creates a new QueueJobProcessed instance.
     */
    public function __construct(
        public string $connection,
        public ReservedJob $job,
        public float $durationMs,
    ) {}
}
