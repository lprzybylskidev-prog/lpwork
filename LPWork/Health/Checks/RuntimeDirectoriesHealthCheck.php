<?php

declare(strict_types=1);

namespace LPWork\Health\Checks;

use LPWork\Filesystem\Filesystem;
use LPWork\Health\Contracts\HealthCheck;
use LPWork\Health\HealthCheckResult;

/**
 * Represents the runtime directories health check framework component.
 */
final readonly class RuntimeDirectoriesHealthCheck implements HealthCheck
{
    /**
     * @param list<string> $directories
     */
    public function __construct(
        private string $basePath,
        private Filesystem $files,
        private array $directories = [
            'storage',
            'storage/cache',
            'storage/framework',
            'storage/framework/cache',
            'storage/logs',
        ],
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'runtime.directories';
    }

    /**
     * Performs the check operation.
     */
    public function check(): HealthCheckResult
    {
        $failures = [];

        foreach ($this->directories as $directory) {
            $path = $this->path($directory);

            if (!$this->files->isDirectory($path)) {
                $failures[] = sprintf('[%s] is missing.', $directory);

                continue;
            }

            if (!$this->files->isReadable($path)) {
                $failures[] = sprintf('[%s] is not readable.', $directory);
            }

            if (!is_writable($path)) {
                $failures[] = sprintf('[%s] is not writable.', $directory);
            }
        }

        if ($failures !== []) {
            return HealthCheckResult::unhealthy($this->name(), implode(' ', $failures));
        }

        return HealthCheckResult::healthy($this->name(), 'Runtime storage, cache, and log directories are readable and writable.');
    }

    private function path(string $directory): string
    {
        return rtrim($this->basePath, '/') . '/' . ltrim($directory, '/');
    }
}
