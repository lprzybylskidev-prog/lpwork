<?php

declare(strict_types=1);

namespace LPWork\Observability\Contracts;

use LPWork\Observability\Metric;

/**
 * Defines the contract for metric reporter.
 */
interface MetricReporter
{
    /**
     * Performs the report operation.
     */
    public function report(Metric $metric): void;
}
