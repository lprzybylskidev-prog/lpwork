<?php

declare(strict_types=1);

namespace LPWork\Health\Checks;

use LPWork\Health\Contracts\HealthCheck;
use LPWork\Health\HealthCheckResult;
use LPWork\Observability\Metric;
use LPWork\Observability\MetricCollector;

/**
 * Represents the observability health check framework component.
 */
final readonly class ObservabilityHealthCheck implements HealthCheck
{
    /**
     * Creates a new ObservabilityHealthCheck instance.
     */
    public function __construct(
        private MetricCollector $metrics,
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'observability';
    }

    /**
     * Performs the check operation.
     */
    public function check(): HealthCheckResult
    {
        $this->metrics->report(new Metric('health.probe', 1, tags: ['source' => 'health']));

        if ($this->metrics->recent() === []) {
            return HealthCheckResult::unhealthy($this->name(), 'Metric collector did not retain the health probe.');
        }

        return HealthCheckResult::healthy($this->name(), 'Metric collector accepts and stores probe metrics.');
    }
}
