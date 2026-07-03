<?php

declare(strict_types=1);

namespace LPWork\Health;

use Throwable;

/**
 * Represents the health runner framework component.
 */
final readonly class HealthRunner
{
    /**
     * Creates a new HealthRunner instance.
     */
    public function __construct(
        private HealthCheckRegistry $checks,
    ) {}

    /**
     * Runs run.
     */
    public function run(): HealthReport
    {
        $results = [];

        foreach ($this->checks->all() as $check) {
            try {
                $results[] = $check->check();
            } catch (Throwable $throwable) {
                $results[] = HealthCheckResult::unhealthy(
                    $check->name(),
                    'Check failed with ' . $throwable::class . '.',
                );
            }
        }

        return new HealthReport($results);
    }
}
