<?php

declare(strict_types=1);

namespace LPWork\Health\Checks;

use LPWork\Health\Contracts\HealthCheck;
use LPWork\Health\HealthCheckResult;
use LPWork\Locks\AtomicLockManager;

/**
 * Represents the locks health check framework component.
 */
final readonly class LocksHealthCheck implements HealthCheck
{
    /**
     * Creates a new LocksHealthCheck instance.
     */
    public function __construct(
        private AtomicLockManager $locks,
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'locks';
    }

    /**
     * Performs the check operation.
     */
    public function check(): HealthCheckResult
    {
        $lock = $this->locks->lock('lpwork.health.' . bin2hex(random_bytes(8)), 30);

        if (!$lock->acquire()) {
            return HealthCheckResult::unhealthy($this->name(), 'Atomic lock could not be acquired.');
        }

        if (!$lock->release()) {
            return HealthCheckResult::unhealthy($this->name(), 'Atomic lock was acquired but could not be released.');
        }

        return HealthCheckResult::healthy($this->name(), 'Atomic locks can be acquired and released.');
    }
}
