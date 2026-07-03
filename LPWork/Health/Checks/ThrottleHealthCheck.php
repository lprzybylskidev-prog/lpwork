<?php

declare(strict_types=1);

namespace LPWork\Health\Checks;

use LPWork\Container\Container;
use LPWork\Health\Contracts\HealthCheck;
use LPWork\Health\HealthCheckResult;
use LPWork\Throttle\ThrottleLimiter;
use LPWork\Throttle\ThrottlePolicy;
use Throwable;

/**
 * Represents the throttle health check framework component.
 */
final readonly class ThrottleHealthCheck implements HealthCheck
{
    /**
     * Creates a new ThrottleHealthCheck instance.
     */
    public function __construct(
        private Container $container,
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'throttle';
    }

    /**
     * Performs the check operation.
     */
    public function check(): HealthCheckResult
    {
        try {
            $limiter = $this->container->make(ThrottleLimiter::class);

            if (!$limiter instanceof ThrottleLimiter) {
                return HealthCheckResult::unhealthy($this->name(), 'Throttle limiter resolved to an invalid object.');
            }

            $result = $limiter->attempt(
                new ThrottlePolicy('lpwork.health.' . bin2hex(random_bytes(4)), true, 2, 60),
                'probe',
            );
        } catch (Throwable $throwable) {
            return HealthCheckResult::unhealthy($this->name(), 'Throttle limiter could not record a health probe: ' . $throwable::class . '.');
        }

        if (!$result->allowed()) {
            return HealthCheckResult::unhealthy($this->name(), 'Throttle storage rejected the first health probe attempt.');
        }

        return HealthCheckResult::healthy($this->name(), 'Throttle limiter can record attempts in the configured storage.');
    }
}
