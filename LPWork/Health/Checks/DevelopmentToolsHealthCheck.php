<?php

declare(strict_types=1);

namespace LPWork\Health\Checks;

use LPWork\Console\ProcessEnvironment;
use LPWork\Health\Contracts\HealthCheck;
use LPWork\Health\HealthCheckResult;

/**
 * Verifies CLI tools used by LPWork's built-in development commands.
 */
final readonly class DevelopmentToolsHealthCheck implements HealthCheck
{
    /**
     * @param list<string> $requiredCommands
     */
    public function __construct(
        private ?string $path = null,
        private ProcessEnvironment $environment = new ProcessEnvironment(),
        private array $requiredCommands = [
            'composer',
            'node',
            'npm',
            'rg',
            'chromium',
            'xauth',
            'xvfb-run',
        ],
    ) {}

    /**
     * Returns the stable health-check key used in reports and CLI output.
     */
    public function name(): string
    {
        return 'development.tools';
    }

    /**
     * Checks that each configured development command is executable on PATH.
     */
    public function check(): HealthCheckResult
    {
        $missing = [];

        foreach ($this->requiredCommands as $command) {
            if ($this->canExecute($command)) {
                continue;
            }

            $missing[] = $command;
        }

        if ($missing !== []) {
            return HealthCheckResult::unhealthy($this->name(), 'Missing development commands: ' . implode(', ', $missing) . '.');
        }

        return HealthCheckResult::healthy($this->name(), 'Composer, Node, search, browser, and headless display tools are available.');
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
