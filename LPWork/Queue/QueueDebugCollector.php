<?php

declare(strict_types=1);

namespace LPWork\Queue;

use LPWork\Observability\Metric;
use LPWork\Observability\MetricCollector;

/**
 * Represents the queue debug collector framework component.
 */
final class QueueDebugCollector
{
    /**
     * @var list<QueueDebugRecord>
     */
    private array $records = [];

    /**
     * Creates a new QueueDebugCollector instance.
     */
    public function __construct(
        private readonly int $limit = 100,
        private readonly ?MetricCollector $metrics = null,
    ) {}

    /**
     * @param array<string, mixed> $context
     */
    public function record(
        string $status,
        string $connection,
        string $queue,
        string $job,
        string $id,
        ?float $durationMs = null,
        array $context = [],
    ): void {
        $this->records[] = new QueueDebugRecord($status, $connection, $queue, $job, $id, $durationMs, $context);

        if (count($this->records) > $this->limit) {
            array_shift($this->records);
        }

        if ($durationMs === null) {
            return;
        }

        $this->metrics?->report(new Metric(
            name: 'queue.job.' . $status,
            value: $durationMs,
            unit: 'ms',
            tags: [
                'connection' => $connection,
                'queue' => $queue,
                'job' => $job,
            ],
        ));
    }

    /**
     * @return list<QueueDebugRecord>
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
