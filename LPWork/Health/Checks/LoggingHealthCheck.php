<?php

declare(strict_types=1);

namespace LPWork\Health\Checks;

use LPWork\Health\Contracts\HealthCheck;
use LPWork\Health\HealthCheckResult;
use LPWork\Logging\LogManager;

/**
 * Represents the logging health check framework component.
 */
final readonly class LoggingHealthCheck implements HealthCheck
{
    /**
     * Creates a new LoggingHealthCheck instance.
     */
    public function __construct(
        private LogManager $logs,
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'logging';
    }

    /**
     * Performs the check operation.
     */
    public function check(): HealthCheckResult
    {
        $this->logs->default()->debug('LPWork health logging probe.');

        return HealthCheckResult::healthy($this->name(), 'Default log channel accepts log records.');
    }
}
