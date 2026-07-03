<?php

declare(strict_types=1);

namespace LPWork\Health\Checks;

use JsonException;
use LPWork\Foundation\RuntimeEnvironment;
use LPWork\Health\Contracts\HealthCheck;
use LPWork\Health\HealthCheckResult;

/**
 * Represents the frontend build output health check framework component.
 */
final readonly class FrontendBuildOutputHealthCheck implements HealthCheck
{
    /**
     * Creates a new FrontendBuildOutputHealthCheck instance.
     */
    public function __construct(
        private FrontendHealthConfiguration $frontend,
        private RuntimeEnvironment $environment,
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'frontend.build';
    }

    /**
     * Performs the check operation.
     */
    public function check(): HealthCheckResult
    {
        if (!$this->frontend->hasFile('vite.config.ts')) {
            return HealthCheckResult::unhealthy($this->name(), 'Missing Vite configuration file [vite.config.ts].');
        }

        if (!$this->frontend->hasFile('public/build/manifest.json')) {
            if ($this->environment->isProduction()) {
                return HealthCheckResult::unhealthy($this->name(), 'Missing Vite build manifest [public/build/manifest.json]. Run php lpwork frontend:build.');
            }

            return HealthCheckResult::healthy($this->name(), 'Vite is configured; build output is not present in this non-production environment.');
        }

        try {
            $manifest = json_decode($this->frontend->read('public/build/manifest.json'), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return HealthCheckResult::unhealthy($this->name(), 'Vite build manifest [public/build/manifest.json] is invalid. Run php lpwork frontend:build.');
        }

        if (!is_array($manifest) || $manifest === []) {
            return HealthCheckResult::unhealthy($this->name(), 'Vite build manifest [public/build/manifest.json] has no entries. Run php lpwork frontend:build.');
        }

        $missing = $this->missingBuiltFiles($manifest);

        if ($missing !== []) {
            return HealthCheckResult::unhealthy($this->name(), 'Missing built frontend assets: ' . implode(', ', $missing) . '. Run php lpwork frontend:build.');
        }

        return HealthCheckResult::healthy($this->name(), 'Vite build manifest and referenced assets are present.');
    }

    /**
     * @param array<array-key, mixed> $manifest
     *
     * @return list<string>
     */
    private function missingBuiltFiles(array $manifest): array
    {
        $missing = [];

        foreach ($manifest as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            foreach ($this->entryFiles($entry) as $file) {
                if (!$this->frontend->hasFile('public/build/' . ltrim($file, '/'))) {
                    $missing[] = $file;
                }
            }
        }

        return $missing;
    }

    /**
     * @param array<array-key, mixed> $entry
     *
     * @return list<string>
     */
    private function entryFiles(array $entry): array
    {
        $files = [];

        if (isset($entry['file']) && is_string($entry['file'])) {
            $files[] = $entry['file'];
        }

        if (isset($entry['css']) && is_array($entry['css'])) {
            foreach ($entry['css'] as $file) {
                if (is_string($file)) {
                    $files[] = $file;
                }
            }
        }

        return $files;
    }
}
