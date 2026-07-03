<?php

declare(strict_types=1);

namespace LPWork\Schedule\Events;

use LPWork\Schedule\ScheduledTask;

/**
 * Represents the scheduled task skipped framework component.
 */
final readonly class ScheduledTaskSkipped
{
    /**
     * Creates a new ScheduledTaskSkipped instance.
     */
    public function __construct(
        public ScheduledTask $task,
        public string $reason,
    ) {}
}
