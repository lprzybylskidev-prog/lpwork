<?php

declare(strict_types=1);

namespace LPWork\Storage;

use LPWork\Config\ArrayConfigReader;
use LPWork\Filesystem\Filesystem;
use LPWork\Storage\Contracts\StorageDriver;
use LPWork\Storage\Drivers\FtpStorageDriver;
use LPWork\Storage\Drivers\LocalStorageDriver;
use LPWork\Storage\Drivers\MemoryStorageDriver;
use LPWork\Storage\Drivers\S3StorageDriver;
use LPWork\Storage\Drivers\SftpStorageDriver;
use LPWork\Storage\Exceptions\InvalidStorageConfigException;
use LPWork\Storage\Exceptions\InvalidStorageDriverException;
use LPWork\Storage\Exceptions\MissingStorageConfigException;

/**
 * Creates storage driver factory instances from framework configuration.
 */
final readonly class StorageDriverFactory
{
    /**
     * Creates a new StorageDriverFactory instance.
     */
    public function __construct(
        private string $basePath,
        private Filesystem $filesystem = new Filesystem(),
    ) {}

    /**
     * @param array<array-key, mixed> $config
     */
    public function create(array $config, string $key): StorageDriver
    {
        $reader = $this->reader($config);
        $driver = $reader->string('driver', "{$key}.driver");

        return match ($driver) {
            'local' => new LocalStorageDriver(
                root: $this->root($reader->string('root', "{$key}.root")),
                filesystem: $this->filesystem,
            ),
            'memory' => new MemoryStorageDriver($this->filesystem),
            's3' => new S3StorageDriver(
                bucket: $reader->string('bucket', "{$key}.bucket"),
                region: $reader->string('region', "{$key}.region"),
                accessKey: $reader->string('access_key', "{$key}.access_key"),
                secretKey: $reader->string('secret_key', "{$key}.secret_key"),
                endpoint: $reader->string('endpoint', "{$key}.endpoint"),
                pathStyle: $reader->optionalBool('path_style', "{$key}.path_style") ?? true,
                filesystem: $this->filesystem,
            ),
            'ftp' => new FtpStorageDriver(
                host: $reader->string('host', "{$key}.host"),
                username: $reader->string('username', "{$key}.username"),
                password: $reader->string('password', "{$key}.password"),
                root: $reader->optionalString('root', "{$key}.root", allowEmpty: true) ?? '',
                port: $reader->int('port', "{$key}.port"),
                timeoutSeconds: $reader->int('timeout_seconds', "{$key}.timeout_seconds"),
                ssl: $reader->optionalBool('ssl', "{$key}.ssl") ?? false,
                passive: $reader->optionalBool('passive', "{$key}.passive") ?? true,
                filesystem: $this->filesystem,
            ),
            'sftp' => new SftpStorageDriver(
                host: $reader->string('host', "{$key}.host"),
                username: $reader->string('username', "{$key}.username"),
                password: $reader->string('password', "{$key}.password"),
                root: $reader->optionalString('root', "{$key}.root", allowEmpty: true) ?? '',
                port: $reader->int('port', "{$key}.port"),
                timeoutSeconds: $reader->int('timeout_seconds', "{$key}.timeout_seconds"),
                filesystem: $this->filesystem,
            ),
            default => throw new InvalidStorageDriverException($driver),
        };
    }

    private function root(string $root): string
    {
        if (str_starts_with($root, '/')) {
            return $root;
        }

        return rtrim($this->basePath, '/') . '/' . ltrim($root, '/');
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private function reader(array $config): ArrayConfigReader
    {
        return new ArrayConfigReader(
            config: $config,
            missingException: static fn(string $key): MissingStorageConfigException => new MissingStorageConfigException($key),
            invalidException: static fn(string $key): InvalidStorageConfigException => new InvalidStorageConfigException($key),
        );
    }
}
