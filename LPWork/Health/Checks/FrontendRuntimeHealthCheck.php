<?php

declare(strict_types=1);

namespace LPWork\Health\Checks;

use LPWork\Console\ProcessEnvironment;
use LPWork\Frontend\FrontendPackageManagerDetector;
use LPWork\Health\Contracts\HealthCheck;
use LPWork\Health\HealthCheckResult;

/**
 * Represents the frontend runtime health check framework component.
 */
final readonly class FrontendRuntimeHealthCheck implements HealthCheck
{
    /**
     * Creates a new FrontendRuntimeHealthCheck instance.
     */
    public function __construct(
        private FrontendHealthConfiguration $frontend,
        private FrontendPackageManagerDetector $packageManagers,
        private ?string $path = null,
        private ProcessEnvironment $environment = new ProcessEnvironment(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'frontend.runtime';
    }

    /**
     * Performs the check operation.
     */
    public function check(): HealthCheckResult
    {
        $failures = [];
        $missingFiles = $this->frontend->missingFiles(['package.json']);

        if ($missingFiles !== []) {
            $failures[] = 'Missing frontend files: ' . implode(', ', $missingFiles) . '.';
        }

        foreach (['node', $this->packageManagers->detect()->value] as $command) {
            if (!$this->canExecute($command)) {
                $failures[] = "Missing frontend command [{$command}].";
            }
        }

        $missingScripts = $this->frontend->missingScripts(['frontend:dev', 'frontend:build']);

        if ($missingScripts !== []) {
            $failures[] = 'Missing frontend runtime scripts: ' . implode(', ', $missingScripts) . '.';
        }

        if ($failures !== []) {
            return HealthCheckResult::unhealthy($this->name(), implode(' ', $failures));
        }

        return HealthCheckResult::healthy($this->name(), 'Node, package manager, package.json, and Vite runtime scripts are available.');
    }

    private function canExecute(string $command): bool
    {
        foreach ($this->paths() as $directory) {
            $candidate = rtrim($directory, '/') . '/' . $command;

            if (is_file($candidate) && is_executable($candidate)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function paths(): array
    {
        $path = $this->path ?? $this->environment->get('PATH');

        if (!is_string($path) || $path === '') {
            return [];
        }

        return array_values(array_filter(
            explode(PATH_SEPARATOR, $path),
            static fn(string $directory): bool => $directory !== '',
        ));
    }
}
