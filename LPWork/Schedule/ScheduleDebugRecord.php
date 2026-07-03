<?php

declare(strict_types=1);

namespace LPWork\Schedule;

/**
 * Represents the schedule debug record framework component.
 */
final readonly class ScheduleDebugRecord
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public string $status,
        public string $task,
        public string $type,
        public string $target,
        public ?float $durationMs = null,
        public array $context = [],
    ) {}
}
