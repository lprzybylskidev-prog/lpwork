<?php

declare(strict_types=1);

namespace LPWork\Schedule;

/**
 * Represents the result of schedule run result work.
 */
final readonly class ScheduleRunResult
{
    /**
     * Creates a new ScheduleRunResult instance.
     */
    public function __construct(
        public int $due,
        public int $ran,
        public int $skipped,
        public int $failed,
    ) {}
}
