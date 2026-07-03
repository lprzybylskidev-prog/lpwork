<?php

declare(strict_types=1);

namespace LPWork\Logging;

use LPWork\Config\ArrayConfigReader;
use LPWork\Logging\Contracts\LogDriver;
use LPWork\Logging\Contracts\LogFormatter;
use LPWork\Logging\Drivers\FallbackLogDriver;
use LPWork\Logging\Drivers\FileLogDriver;
use LPWork\Logging\Drivers\StreamLogDriver;
use LPWork\Logging\Drivers\SyslogLogDriver;
use LPWork\Logging\Enums\LogRotation;
use LPWork\Logging\Exceptions\InvalidLogConfigException;
use LPWork\Logging\Exceptions\InvalidLogDriverException;
use LPWork\Logging\Exceptions\MissingLogConfigException;
use LPWork\Storage\StorageDisk;
use LPWork\Storage\StorageManager;

/**
 * Creates log driver factory instances from framework configuration.
 */
final readonly class LogDriverFactory
{
    /**
     * Creates a new LogDriverFactory instance.
     */
    public function __construct(
        private string $basePath,
        private LogFormatterFactory $formatterFactory = new LogFormatterFactory(),
        private ?StorageManager $storage = null,
    ) {}

    /**
     * @param array<array-key, mixed> $config
     */
    public function create(array $config, string $key): LogDriver
    {
        $reader = $this->reader($config);
        $driver = $reader->string('driver', "{$key}.driver");

        return match ($driver) {
            'file' => $this->fileDriver($config, $key),
            'fallback' => $this->fallbackDriver($config, $key),
            'stdout' => new StreamLogDriver('php://stdout', $this->formatterFactory->create($config, "{$key}.format")),
            'stderr' => new StreamLogDriver('php://stderr', $this->formatterFactory->create($config, "{$key}.format")),
            'syslog' => new SyslogLogDriver(
                ident: $reader->optionalString('ident', "{$key}.ident") ?? 'lpwork',
                formatter: $this->formatterFactory->create($config, "{$key}.format"),
            ),
            default => throw new InvalidLogDriverException($driver),
        };
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private function fileDriver(array $config, string $key): LogDriver
    {
        $reader = $this->reader($config);
        return $this->makeFileDriver(
            path: $reader->string('path', "{$key}.path"),
            rotation: $this->rotationConfig($config, "{$key}.rotation"),
            formatter: $this->formatterFactory->create($config, "{$key}.format"),
            disk: $this->disk($reader->optionalString('disk', "{$key}.disk")),
        );
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private function fallbackDriver(array $config, string $key): LogDriver
    {
        $reader = $this->reader($config);
        $primary = $reader->array('primary', "{$key}.primary");
        $fallback = $reader->array('fallback', "{$key}.fallback");

        return new FallbackLogDriver(
            primary: $this->create($primary, "{$key}.primary"),
            fallback: $this->create($fallback, "{$key}.fallback"),
        );
    }

    private function makeFileDriver(string $path, ?LogRotation $rotation, LogFormatter $formatter, ?StorageDisk $disk): FileLogDriver
    {
        return new FileLogDriver(
            path: $path,
            rotation: $rotation,
            formatter: $formatter,
            basePath: $this->basePath,
            disk: $disk,
        );
    }

    private function disk(?string $name): ?StorageDisk
    {
        if ($name === null || $this->storage === null) {
            return null;
        }

        return $this->storage->disk($name);
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private function rotationConfig(array $config, string $key): ?LogRotation
    {
        $rotation = $this->reader($config)->optionalString('rotation', $key);

        if ($rotation === null) {
            return null;
        }

        $logRotation = LogRotation::tryFrom($rotation);

        if ($logRotation === null) {
            throw new InvalidLogConfigException($key);
        }

        return $logRotation;
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private function reader(array $config): ArrayConfigReader
    {
        return new ArrayConfigReader(
            config: $config,
            missingException: static fn(string $key): MissingLogConfigException => new MissingLogConfigException($key),
            invalidException: static fn(string $key): InvalidLogConfigException => new InvalidLogConfigException($key),
        );
    }
}
