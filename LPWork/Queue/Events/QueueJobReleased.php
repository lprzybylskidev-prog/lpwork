<?php

declare(strict_types=1);

namespace LPWork\Queue\Events;

use LPWork\Queue\ReservedJob;
use Throwable;

/**
 * Represents the queue job released framework component.
 */
final readonly class QueueJobReleased
{
    /**
     * Creates a new QueueJobReleased instance.
     */
    public function __construct(
        public string $connection,
        public ReservedJob $job,
        public int $delaySeconds,
        public Throwable $throwable,
    ) {}
}
