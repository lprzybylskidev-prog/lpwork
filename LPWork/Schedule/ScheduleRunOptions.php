<?php

declare(strict_types=1);

namespace LPWork\Schedule;

/**
 * Carries options for schedule run options behavior.
 */
final readonly class ScheduleRunOptions
{
    /**
     * Creates a new ScheduleRunOptions instance.
     */
    public function __construct(
        public ?string $task = null,
        public bool $force = false,
    ) {}
}
