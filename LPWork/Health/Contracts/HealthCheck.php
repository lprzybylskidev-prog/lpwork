<?php

declare(strict_types=1);

namespace LPWork\Health\Contracts;

use LPWork\Health\HealthCheckResult;

/**
 * Defines the contract for health check.
 */
interface HealthCheck
{
    /**
     * Returns the configured name for this object.
     */
    public function name(): string;

    /**
     * Performs the check operation.
     */
    public function check(): HealthCheckResult;
}
