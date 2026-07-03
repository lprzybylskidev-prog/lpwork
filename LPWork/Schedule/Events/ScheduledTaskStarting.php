<?php

declare(strict_types=1);

namespace LPWork\Schedule\Events;

use LPWork\Schedule\ScheduledTask;

/**
 * Represents the scheduled task starting framework component.
 */
final readonly class ScheduledTaskStarting
{
    /**
     * Creates a new ScheduledTaskStarting instance.
     */
    public function __construct(
        public ScheduledTask $task,
    ) {}
}
