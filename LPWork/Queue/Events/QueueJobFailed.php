<?php

declare(strict_types=1);

namespace LPWork\Queue\Events;

use LPWork\Queue\ReservedJob;
use Throwable;

/**
 * Represents the queue job failed framework component.
 */
final readonly class QueueJobFailed
{
    /**
     * Creates a new QueueJobFailed instance.
     */
    public function __construct(
        public string $connection,
        public ReservedJob $job,
        public Throwable $throwable,
    ) {}
}
