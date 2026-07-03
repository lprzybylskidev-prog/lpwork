<?php

declare(strict_types=1);

namespace LPWork\Health\Checks;

use LPWork\Health\Contracts\HealthCheck;
use LPWork\Health\HealthCheckResult;

/**
 * Represents the frontend testing health check framework component.
 */
final readonly class FrontendTestingHealthCheck implements HealthCheck
{
    /**
     * Creates a new FrontendTestingHealthCheck instance.
     */
    public function __construct(
        private FrontendHealthConfiguration $frontend,
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'frontend.testing';
    }

    /**
     * Performs the check operation.
     */
    public function check(): HealthCheckResult
    {
        $failures = [];
        $missingFiles = $this->frontend->missingFiles([
            'vitest.config.ts',
            'playwright.config.mjs',
        ]);
        $missingScripts = $this->frontend->missingScripts([
            'frontend:test',
            'browser:test',
            'browser:install',
        ]);
        $missingDependencies = $this->frontend->missingDevDependencies([
            'vitest',
            '@playwright/test',
        ]);

        if ($missingFiles !== []) {
            $failures[] = 'Missing frontend testing config files: ' . implode(', ', $missingFiles) . '.';
        }

        if ($missingScripts !== []) {
            $failures[] = 'Missing frontend testing scripts: ' . implode(', ', $missingScripts) . '.';
        }

        if ($missingDependencies !== []) {
            $failures[] = 'Missing frontend testing dependencies: ' . implode(', ', $missingDependencies) . '.';
        }

        if ($failures !== []) {
            return HealthCheckResult::unhealthy($this->name(), implode(' ', $failures));
        }

        return HealthCheckResult::healthy($this->name(), 'Vitest and Playwright diagnostics are configured.');
    }
}
