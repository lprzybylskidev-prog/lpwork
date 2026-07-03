<?php

declare(strict_types=1);

namespace LPWork\Observability;

use LPWork\Observability\Contracts\MetricReporter;

/**
 * Represents the metric collector framework component.
 */
final class MetricCollector implements MetricReporter
{
    /**
     * @var list<Metric>
     */
    private array $metrics = [];

    /**
     * @param list<MetricReporter> $reporters
     */
    public function __construct(
        private readonly array $reporters = [],
        private readonly int $limit = 100,
    ) {}

    /**
     * Performs the report operation.
     */
    public function report(Metric $metric): void
    {
        if ($metric->recordedAtMs <= 0 || $metric->memoryBytes <= 0) {
            $metric = new Metric(
                name: $metric->name,
                value: $metric->value,
                unit: $metric->unit,
                tags: $metric->tags,
                recordedAtMs: $metric->recordedAtMs > 0 ? $metric->recordedAtMs : round(microtime(true) * 1000, 3),
                memoryBytes: $metric->memoryBytes > 0 ? $metric->memoryBytes : memory_get_usage(true),
            );
        }

        $this->metrics[] = $metric;

        if (count($this->metrics) > $this->limit) {
            array_shift($this->metrics);
        }

        foreach ($this->reporters as $reporter) {
            $reporter->report($metric);
        }
    }

    /**
     * @return list<Metric>
     */
    public function recent(): array
    {
        return $this->metrics;
    }

    /**
     * Resets this component to an empty lifecycle state.
     */
    public function reset(): void
    {
        $this->metrics = [];
    }
}
