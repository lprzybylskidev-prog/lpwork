<?php

declare(strict_types=1);

namespace LPWork\Observability;

use LPWork\Logging\Contracts\Logger;
use LPWork\Observability\Contracts\MetricReporter;

/**
 * Represents the log metric reporter framework component.
 */
final readonly class LogMetricReporter implements MetricReporter
{
    /**
     * Creates a new LogMetricReporter instance.
     */
    public function __construct(private Logger $logger) {}

    /**
     * Performs the report operation.
     */
    public function report(Metric $metric): void
    {
        $this->logger->info('Metric recorded', [
            'name' => $metric->name,
            'value' => $metric->value,
            'unit' => $metric->unit,
            'tags' => $metric->tags,
            'recorded_at_ms' => $metric->recordedAtMs,
            'memory_bytes' => $metric->memoryBytes,
        ]);
    }
}
