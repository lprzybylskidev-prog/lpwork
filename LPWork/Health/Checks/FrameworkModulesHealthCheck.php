<?php

declare(strict_types=1);

namespace LPWork\Health\Checks;

use LPWork\Foundation\FrameworkModuleCatalog;
use LPWork\Foundation\FrameworkModuleRegistry;
use LPWork\Health\Contracts\HealthCheck;
use LPWork\Health\HealthCheckResult;

/**
 * Represents the framework modules health check framework component.
 */
final readonly class FrameworkModulesHealthCheck implements HealthCheck
{
    /**
     * Creates a new FrameworkModulesHealthCheck instance.
     */
    public function __construct(
        private FrameworkModuleCatalog $catalog,
        private FrameworkModuleRegistry $registry,
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'framework.modules';
    }

    /**
     * Performs the check operation.
     */
    public function check(): HealthCheckResult
    {
        $catalogCount = $this->catalog->count();
        $registryCount = $this->registry->count();

        if ($catalogCount !== $registryCount) {
            return HealthCheckResult::unhealthy($this->name(), sprintf('Module catalog has %d entries but bootstrap registry has %d.', $catalogCount, $registryCount));
        }

        return HealthCheckResult::healthy($this->name(), sprintf('Framework module catalog and bootstrap registry agree on %d module(s).', $catalogCount));
    }
}
