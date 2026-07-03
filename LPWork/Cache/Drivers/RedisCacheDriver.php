<?php

declare(strict_types=1);

namespace LPWork\Cache\Drivers;

use LPWork\Cache\Contracts\CacheDriver;
use LPWork\Cache\Exceptions\InvalidCacheKeyException;
use LPWork\Cache\Exceptions\InvalidCacheTtlException;
use LPWork\Shared\Redis\RedisClient;

/**
 * Represents the redis cache driver framework component.
 */
final readonly class RedisCacheDriver implements CacheDriver
{
    /**
     * Creates a new RedisCacheDriver instance.
     */
    public function __construct(private RedisClient $redis) {}

    /**
     * Returns the requested value from this component.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->redis->get($this->key($key));

        return is_string($value) ? unserialize($value) : $default;
    }

    /**
     * Stores or replaces a value in this component's backing store.
     */
    public function put(string $key, mixed $value, ?int $ttlSeconds = null): void
    {
        if ($ttlSeconds !== null) {
            $this->assertValidTtl($ttlSeconds);
        }

        $this->redis->set($this->key($key), serialize($value), $ttlSeconds);
    }

    /**
     * Adds an item to this component's registry or backing store.
     */
    public function add(string $key, mixed $value, int $ttlSeconds): bool
    {
        $this->assertValidTtl($ttlSeconds);

        return $this->redis->add($this->key($key), serialize($value), $ttlSeconds);
    }

    /**
     * Removes a value from this component's backing store.
     */
    public function forget(string $key): void
    {
        $this->redis->delete($this->key($key));
    }

    /**
     * Removes or clears forget if value.
     */
    public function forgetIfValue(string $key, mixed $value): bool
    {
        return $this->redis->deleteIfValue($this->key($key), serialize($value));
    }

    /**
     * Clears the state owned by this component.
     */
    public function clear(): void
    {
        $this->redis->clearPattern('cache:*');
    }

    private function key(string $key): string
    {
        if ($key === '') {
            throw new InvalidCacheKeyException();
        }

        return 'cache:' . $key;
    }

    private function assertValidTtl(int $ttlSeconds): void
    {
        if ($ttlSeconds <= 0) {
            throw new InvalidCacheTtlException();
        }
    }
}
