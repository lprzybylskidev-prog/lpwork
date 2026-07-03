<?php

declare(strict_types=1);

namespace LPWork\Health\Checks;

use LPWork\Health\Contracts\HealthCheck;
use LPWork\Health\Contracts\PhpRuntimeInspector;
use LPWork\Health\HealthCheckResult;

/**
 * Verifies optional PHP extensions used by LPWork development workflows and configurable drivers.
 */
final readonly class DevelopmentPhpExtensionsHealthCheck implements HealthCheck
{
    /**
     * @param list<string> $requiredExtensions
     */
    public function __construct(
        private PhpRuntimeInspector $runtime,
        private array $requiredExtensions = [
            'apcu',
            'ftp',
            'intl',
            'pdo_mysql',
            'pdo_pgsql',
            'pdo_sqlite',
            'redis',
            'ssh2',
            'xdebug',
            'zip',
        ],
    ) {}

    /**
     * Returns the stable health-check key used in reports and CLI output.
     */
    public function name(): string
    {
        return 'development.php_extensions';
    }

    /**
     * Checks that each configured development PHP extension is loaded.
     */
    public function check(): HealthCheckResult
    {
        $missing = [];

        foreach ($this->requiredExtensions as $extension) {
            if ($this->runtime->extensionLoaded($extension)) {
                continue;
            }

            $missing[] = $extension;
        }

        if ($missing !== []) {
            return HealthCheckResult::unhealthy($this->name(), 'Missing development PHP extensions: ' . implode(', ', $missing) . '.');
        }

        return HealthCheckResult::healthy($this->name(), 'Development PHP extensions for configured drivers, debugging, and archives are loaded.');
    }
}
