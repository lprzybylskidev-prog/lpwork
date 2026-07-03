<?php

declare(strict_types=1);

namespace LPWork\Health\Checks;

use LPWork\Config\Config;
use LPWork\Health\Contracts\HealthCheck;
use LPWork\Health\Contracts\PhpRuntimeInspector;
use LPWork\Health\HealthCheckResult;
use Throwable;

/**
 * Represents the php runtime health check framework component.
 */
final readonly class PhpRuntimeHealthCheck implements HealthCheck
{
    private const MINIMUM_PHP_VERSION = '8.5';

    private const MINIMUM_PHP_VERSION_ID = 80500;

    /**
     * @var list<string>
     */
    private const REQUIRED_EXTENSIONS = [
        'json',
        'pdo',
    ];

    /**
     * Creates a new PhpRuntimeHealthCheck instance.
     */
    public function __construct(
        private PhpRuntimeInspector $runtime,
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'php';
    }

    /**
     * Performs the check operation.
     */
    public function check(): HealthCheckResult
    {
        $failures = [
            ...$this->phpVersionFailures(),
            ...$this->extensionFailures(),
            ...$this->pdoDriverFailures(),
        ];

        if ($failures !== []) {
            return HealthCheckResult::unhealthy($this->name(), implode(' ', $failures));
        }

        return HealthCheckResult::healthy($this->name(), 'PHP runtime meets framework requirements.');
    }

    /**
     * @return list<string>
     */
    private function phpVersionFailures(): array
    {
        if ($this->runtime->phpVersionId() >= self::MINIMUM_PHP_VERSION_ID) {
            return [];
        }

        return [
            sprintf(
                'PHP %s or newer is required; current version is %s.',
                self::MINIMUM_PHP_VERSION,
                $this->runtime->phpVersion(),
            ),
        ];
    }

    /**
     * @return list<string>
     */
    private function extensionFailures(): array
    {
        $failures = [];

        foreach ($this->requiredExtensions() as $extension) {
            if ($this->runtime->extensionLoaded($extension)) {
                continue;
            }

            $failures[] = sprintf('Missing PHP extension [%s].', $extension);
        }

        return $failures;
    }

    /**
     * @return list<string>
     */
    private function pdoDriverFailures(): array
    {
        try {
            $driver = $this->configuredDatabaseDriver();
        } catch (Throwable $throwable) {
            return [
                sprintf('Database runtime requirement could not be read from configuration: %s.', $throwable::class),
            ];
        }

        if ($driver === null || in_array($driver, $this->runtime->pdoDrivers(), true)) {
            return [];
        }

        return [
            sprintf('Missing PDO driver [%s] for the configured default database connection.', $driver),
        ];
    }

    /**
     * @return list<string>
     */
    private function requiredExtensions(): array
    {
        $extensions = self::REQUIRED_EXTENSIONS;

        if ($this->configuredSessionDriver() === 'php') {
            $extensions[] = 'session';
        }

        foreach ($this->configuredDriverExtensions() as $extension) {
            $extensions[] = $extension;
        }

        $extensions = array_values(array_unique($extensions));

        return $extensions;
    }

    /**
     * @return list<string>
     */
    private function configuredDriverExtensions(): array
    {
        $extensions = [];

        if ($this->configuredCacheDriver() === 'redis'
            || $this->configuredQueueDriver() === 'redis'
            || $this->configuredSessionDriver() === 'redis'
            || $this->configuredBroadcastDriver() === 'redis'
            || $this->configuredThrottleStorage() === 'redis'
            || $this->configuredLockDriver() === 'redis') {
            $extensions[] = 'redis';
        }

        if ($this->configuredCacheDriver() === 'apcu') {
            $extensions[] = 'apcu';
        }

        if ($this->configuredStorageDriver() === 'ftp') {
            $extensions[] = 'ftp';
        }

        if ($this->configuredStorageDriver() === 'sftp') {
            $extensions[] = 'ssh2';
        }

        return $extensions;
    }

    private function configuredSessionDriver(): ?string
    {
        try {
            return Config::getString('session.default');
        } catch (Throwable) {
            return null;
        }
    }

    private function configuredDatabaseDriver(): ?string
    {
        $connection = Config::getString('database.default');
        $config = Config::getArray("database.connections.{$connection}");
        $driver = $config['driver'] ?? null;

        return is_string($driver) && $driver !== '' ? $driver : null;
    }

    private function configuredCacheDriver(): ?string
    {
        return $this->configuredNamedDriver('cache', 'default', 'stores');
    }

    private function configuredStorageDriver(): ?string
    {
        return $this->configuredNamedDriver('storage', 'default', 'disks');
    }

    private function configuredQueueDriver(): ?string
    {
        return $this->configuredNamedDriver('queue', 'default', 'connections');
    }

    private function configuredBroadcastDriver(): ?string
    {
        return $this->configuredNamedDriver('broadcasting', 'default', 'connections');
    }

    private function configuredThrottleStorage(): ?string
    {
        try {
            return Config::getString('throttle.storage');
        } catch (Throwable) {
            return null;
        }
    }

    private function configuredLockDriver(): ?string
    {
        try {
            return Config::getString('locks.driver');
        } catch (Throwable) {
            return null;
        }
    }

    private function configuredNamedDriver(string $root, string $defaultKey, string $mapKey): ?string
    {
        try {
            $name = Config::getString("{$root}.{$defaultKey}");
            $config = Config::getArray("{$root}.{$mapKey}.{$name}");
            $driver = $config['driver'] ?? null;

            return is_string($driver) && $driver !== '' ? $driver : null;
        } catch (Throwable) {
            return null;
        }
    }
}
