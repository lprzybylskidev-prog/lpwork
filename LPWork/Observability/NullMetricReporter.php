<?php

declare(strict_types=1);

namespace LPWork\Observability;

use LPWork\Observability\Contracts\MetricReporter;

/**
 * Represents the null metric reporter framework component.
 */
final readonly class NullMetricReporter implements MetricReporter
{
    /**
     * Performs the report operation.
     */
    public function report(Metric $metric): void {}
}
