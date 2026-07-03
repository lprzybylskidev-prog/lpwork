<?php

declare(strict_types=1);

namespace LPWork\Schedule;

use DateTimeImmutable;

/**
 * Stores and resolves schedule registry registrations.
 */
final class ScheduleRegistry
{
    /**
     * @var list<PendingScheduledTask>
     */
    private array $tasks = [];

    /**
     * @param list<string> $arguments
     * @param array<string, string|bool|int> $options
     */
    public function command(string $command, array $arguments = [], array $options = [], ?string $name = null): PendingScheduledTask
    {
        return $this->add(new PendingScheduledTask(
            name: $name ?? $command,
            type: ScheduledTaskType::Command,
            target: $command,
            arguments: $arguments,
            options: $options,
        ));
    }

    /**
     * Performs the job operation.
     */
    public function job(object $job, ?string $name = null): PendingScheduledTask
    {
        return $this->add(new PendingScheduledTask(
            name: $name ?? $job::class,
            type: ScheduledTaskType::Job,
            target: $job::class,
            job: $job,
        ));
    }

    /**
     * @return list<ScheduledTask>
     */
    public function all(): array
    {
        return array_map(static fn(PendingScheduledTask $task): ScheduledTask => $task->task(), $this->tasks);
    }

    /**
     * @return list<ScheduledTask>
     */
    public function due(DateTimeImmutable $now): array
    {
        return array_values(array_filter(
            $this->all(),
            static fn(ScheduledTask $task): bool => $task->isDue($now),
        ));
    }

    private function add(PendingScheduledTask $task): PendingScheduledTask
    {
        $this->tasks[] = $task;

        return $task;
    }
}
