<?php

declare(strict_types=1);

namespace LPWork\Schedule\Events;

use LPWork\Schedule\ScheduledTask;

/**
 * Represents the scheduled task succeeded framework component.
 */
final readonly class ScheduledTaskSucceeded
{
    /**
     * Creates a new ScheduledTaskSucceeded instance.
     */
    public function __construct(
        public ScheduledTask $task,
        public float $durationMs,
    ) {}
}
