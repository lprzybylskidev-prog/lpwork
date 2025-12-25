<?php
declare(strict_types=1);

namespace LPwork\ErrorLog;

use LPwork\ErrorLog\Exception\ErrorLogConfigurationException;
use LPwork\Config\Support\ConfigNormalizer;

/**
 * Typed configuration for error logging.
 */
final class ErrorLogConfiguration
{
    use ConfigNormalizer;

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
        $this->driver = $this->stringVal(
            $config['driver'] ?? null,
            'error_log.driver',
            'file',
            false,
        );
        $this->level = $this->stringVal(
            $config['level'] ?? null,
            'error_log.level',
            'error',
            false,
        );
        $this->file = (array) ($config['file'] ?? []);
        $this->database = (array) ($config['database'] ?? []);
        $this->redis = (array) ($config['redis'] ?? []);
        $this->logClientErrors = $this->boolVal(
            $config['log_client_errors'] ?? null,
            'error_log.log_client_errors',
            false,
        );

        $this->assertSupportedDriver($this->driver);

        if ($this->driver === 'file' && ($this->file['directory'] ?? '') === '') {
            throw new ErrorLogConfigurationException('File error log requires "directory" path.');
        }

        if (
            $this->driver === 'database' &&
            !isset($this->database['connection'], $this->database['table'])
        ) {
            throw new ErrorLogConfigurationException(
                'Database error log requires "connection" and "table".',
            );
        }

        if (
            $this->driver === 'redis' &&
            !isset($this->redis['connection'], $this->redis['prefix'])
        ) {
            throw new ErrorLogConfigurationException(
                'Redis error log requires "connection" and "prefix".',
            );
        }
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
