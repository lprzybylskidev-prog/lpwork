<?php

declare(strict_types=1);

namespace LPWork\Cache;

use LPWork\Cache\Contracts\CacheDriver;
use LPWork\Cache\Drivers\ApcuCacheDriver;
use LPWork\Cache\Drivers\DatabaseCacheDriver;
use LPWork\Cache\Drivers\FileCacheDriver;
use LPWork\Cache\Drivers\RedisCacheDriver;
use LPWork\Cache\Exceptions\InvalidCacheConfigException;
use LPWork\Cache\Exceptions\InvalidCacheDriverException;
use LPWork\Cache\Exceptions\MissingCacheConfigException;
use LPWork\Config\ArrayConfigReader;
use LPWork\Database\Contracts\Connection;
use LPWork\Database\DatabaseManager;
use LPWork\Shared\Redis\RedisClient;
use LPWork\Shared\Redis\RedisConfigFactory;
use LPWork\Storage\StorageDisk;
use LPWork\Storage\StorageManager;
use LPWork\Time\Contracts\Clock;
use LPWork\Time\SystemClock;

/**
 * Creates cache driver factory instances from framework configuration.
 */
final readonly class CacheDriverFactory
{
    /**
     * Creates a new CacheDriverFactory instance.
     */
    public function __construct(
        private string $basePath,
        private ?StorageManager $storage = null,
        private ?DatabaseManager $database = null,
        private Clock $clock = new SystemClock(),
        private RedisConfigFactory $redis = new RedisConfigFactory(),
    ) {}

    /**
     * @param array<array-key, mixed> $config
     */
    public function create(array $config, string $key): CacheDriver
    {
        $reader = $this->reader($config);
        $driver = $reader->string('driver', "{$key}.driver");

        return match ($driver) {
            'file' => new FileCacheDriver(
                path: $reader->string('path', "{$key}.path"),
                basePath: $this->basePath,
                disk: $this->disk($reader->optionalString('disk', "{$key}.disk")),
            ),
            'redis' => new RedisCacheDriver(new RedisClient($this->redis->create($reader, $config, $key), "cache store [{$key}]")),
            'apcu' => new ApcuCacheDriver($reader->optionalString('prefix', "{$key}.prefix", allowEmpty: true) ?? ''),
            'database' => new DatabaseCacheDriver(
                connection: $this->database($reader->optionalString('connection', "{$key}.connection", allowEmpty: true)),
                table: $reader->string('table', "{$key}.table"),
                clock: $this->clock,
            ),
            default => throw new InvalidCacheDriverException($driver),
        };
    }

    private function disk(?string $name): ?StorageDisk
    {
        if ($name === null || $this->storage === null) {
            return null;
        }

        return $this->storage->disk($name);
    }

    private function database(?string $connection): Connection
    {
        if ($this->database === null) {
            throw new MissingCacheConfigException('database');
        }

        if ($connection === null || $connection === '') {
            return $this->database->default();
        }

        return $this->database->connection($connection);
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private function reader(array $config): ArrayConfigReader
    {
        return new ArrayConfigReader(
            config: $config,
            missingException: static fn(string $key): MissingCacheConfigException => new MissingCacheConfigException($key),
            invalidException: static fn(string $key): InvalidCacheConfigException => new InvalidCacheConfigException($key),
        );
    }
}
