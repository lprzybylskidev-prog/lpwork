<?php

declare(strict_types=1);

namespace LPWork\Schedule\Events;

use LPWork\Schedule\ScheduledTask;
use Throwable;

/**
 * Represents the scheduled task failed framework component.
 */
final readonly class ScheduledTaskFailed
{
    /**
     * Creates a new ScheduledTaskFailed instance.
     */
    public function __construct(
        public ScheduledTask $task,
        public Throwable $throwable,
    ) {}
}
