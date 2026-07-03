<?php

declare(strict_types=1);

namespace LPWork\Schedule;

use DateTimeImmutable;

/**
 * Represents the scheduled task framework component.
 */
final readonly class ScheduledTask
{
    /**
     * @param list<string> $arguments
     * @param array<string, string|bool|int> $options
     */
    public function __construct(
        public string $name,
        public ScheduledTaskType $type,
        public string $target,
        public ScheduleFrequency $frequency,
        public array $arguments = [],
        public array $options = [],
        public ?object $job = null,
        public bool $withoutOverlapping = true,
        public ?string $queueConnection = null,
        public ?string $queue = null,
        public int $delaySeconds = 0,
    ) {}

    /**
     * Reports whether is due.
     */
    public function isDue(DateTimeImmutable $now): bool
    {
        return $this->frequency->isDue($now);
    }

    /**
     * Performs the lock name operation.
     */
    public function lockName(): string
    {
        return sprintf('schedule:%s:%s', $this->type->value, $this->name);
    }
}
