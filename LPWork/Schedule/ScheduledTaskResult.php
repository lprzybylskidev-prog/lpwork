<?php

declare(strict_types=1);

namespace LPWork\Schedule;

/**
 * Represents the result of scheduled task result work.
 */
final readonly class ScheduledTaskResult
{
    /**
     * Creates a new ScheduledTaskResult instance.
     */
    public function __construct(
        public int $exitCode,
        public string $message,
    ) {}
}
