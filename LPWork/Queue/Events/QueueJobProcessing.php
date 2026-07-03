<?php

declare(strict_types=1);

namespace LPWork\Queue\Events;

use LPWork\Queue\ReservedJob;

/**
 * Represents the queue job processing framework component.
 */
final readonly class QueueJobProcessing
{
    /**
     * Creates a new QueueJobProcessing instance.
     */
    public function __construct(
        public string $connection,
        public ReservedJob $job,
    ) {}
}
