<?php

declare(strict_types=1);

namespace LPWork\Health\Checks;

use LPWork\Health\Contracts\HealthCheck;
use LPWork\Health\HealthCheckResult;

/**
 * Represents the frontend quality health check framework component.
 */
final readonly class FrontendQualityHealthCheck implements HealthCheck
{
    /**
     * Creates a new FrontendQualityHealthCheck instance.
     */
    public function __construct(
        private FrontendHealthConfiguration $frontend,
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'frontend.quality';
    }

    /**
     * Performs the check operation.
     */
    public function check(): HealthCheckResult
    {
        $failures = [];
        $missingFiles = $this->frontend->missingFiles([
            'tsconfig.json',
            'eslint.config.js',
            'prettier.config.js',
            'stylelint.config.js',
        ]);
        $missingScripts = $this->frontend->missingScripts([
            'frontend:typecheck',
            'frontend:lint',
            'frontend:stylelint',
            'frontend:format',
            'frontend:check',
        ]);
        $missingDependencies = $this->frontend->missingDevDependencies([
            'typescript',
            'eslint',
            'prettier',
            'stylelint',
        ]);

        if ($missingFiles !== []) {
            $failures[] = 'Missing frontend quality config files: ' . implode(', ', $missingFiles) . '.';
        }

        if ($missingScripts !== []) {
            $failures[] = 'Missing frontend quality scripts: ' . implode(', ', $missingScripts) . '.';
        }

        if ($missingDependencies !== []) {
            $failures[] = 'Missing frontend quality dependencies: ' . implode(', ', $missingDependencies) . '.';
        }

        if ($failures !== []) {
            return HealthCheckResult::unhealthy($this->name(), implode(' ', $failures));
        }

        return HealthCheckResult::healthy($this->name(), 'TypeScript, ESLint, Stylelint, and Prettier diagnostics are configured.');
    }
}
