<?php

declare(strict_types=1);

namespace LPWork\Schedule;

use LPWork\Observability\Metric;
use LPWork\Observability\MetricCollector;

/**
 * Represents the schedule debug collector framework component.
 */
final class ScheduleDebugCollector
{
    /**
     * @var list<ScheduleDebugRecord>
     */
    private array $records = [];

    /**
     * Creates a new ScheduleDebugCollector instance.
     */
    public function __construct(
        private readonly int $limit = 100,
        private readonly ?MetricCollector $metrics = null,
    ) {}

    /**
     * @param array<string, mixed> $context
     */
    public function record(string $status, ScheduledTask $task, ?float $durationMs = null, array $context = []): void
    {
        $this->recordRaw(
            status: $status,
            task: $task->name,
            type: $task->type->value,
            target: $task->target,
            durationMs: $durationMs,
            context: [
                'Frequency' => $task->frequency->expression(),
                'Without overlapping' => $task->withoutOverlapping,
            ] + $context,
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    public function recordRaw(string $status, string $task, string $type, string $target, ?float $durationMs = null, array $context = []): void
    {
        $this->records[] = new ScheduleDebugRecord($status, $task, $type, $target, $durationMs, $context);

        if (count($this->records) > $this->limit) {
            array_shift($this->records);
        }

        if ($durationMs === null) {
            return;
        }

        $this->metrics?->report(new Metric(
            name: 'scheduler.task.' . $status,
            value: $durationMs,
            unit: 'ms',
            tags: [
                'task' => $task,
                'type' => $type,
            ],
        ));
    }

    /**
     * @return list<ScheduleDebugRecord>
     */
    public function recent(): array
    {
        return $this->records;
    }

    /**
     * Resets this component to an empty lifecycle state.
     */
    public function reset(): void
    {
        $this->records = [];
    }
}
