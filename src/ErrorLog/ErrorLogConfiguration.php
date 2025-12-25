<?php
declare(strict_types=1);

namespace LPwork\ErrorLog;

use LPwork\ErrorLog\Exception\ErrorLogConfigurationException;

/**
 * Typed configuration for error logging.
 */
final class ErrorLogConfiguration
{
    /**
     * @var string
     */
    private string $driver;

    /**
     * @var string
     */
    private string $level;

    /**
     * @var array<string, mixed>
     */
    private array $file;

    /**
     * @var array<string, mixed>
     */
    private array $database;

    /**
     * @var array<string, mixed>
     */
    private array $redis;

    /**
     * @var bool
     */
    private bool $logClientErrors;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $this->driver = (string) ($config['driver'] ?? 'file');
        $this->level = (string) ($config['level'] ?? 'error');
        $this->file = (array) ($config['file'] ?? []);
        $this->database = (array) ($config['database'] ?? []);
        $this->redis = (array) ($config['redis'] ?? []);
        $this->logClientErrors = (bool) ($config['log_client_errors'] ?? false);
    }

    /**
     * @return string
     */
    public function driver(): string
    {
        return $this->driver;
    }

    /**
     * @return string
     */
    public function level(): string
    {
        return $this->level;
    }

    /**
     * @return array<string, mixed>
     */
    public function file(): array
    {
        return $this->file;
    }

    /**
     * @return array<string, mixed>
     */
    public function database(): array
    {
        return $this->database;
    }

    /**
     * @return array<string, mixed>
     */
    public function redis(): array
    {
        return $this->redis;
    }

    /**
     * @return bool
     */
    public function logClientErrors(): bool
    {
        return $this->logClientErrors;
    }

    /**
     * @param string $driver
     *
     * @return void
     */
    public function assertSupportedDriver(string $driver): void
    {
        $supported = ['file', 'database', 'redis'];

        if (!\in_array($driver, $supported, true)) {
            throw new ErrorLogConfigurationException(
                \sprintf('Error log driver "%s" is not supported.', $driver),
            );
        }
    }
}
