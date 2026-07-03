<?php

declare(strict_types=1);

namespace LPWork\Cache\Contracts;

/**
 * Defines the low-level operations required by cache store drivers.
 */
interface CacheDriver
{
    /**
     * Returns a cached value or the supplied default when the key is missing or expired.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Stores a cached value, optionally expiring it after the given number of seconds.
     */
    public function put(string $key, mixed $value, ?int $ttlSeconds = null): void;

    /**
     * Stores a cached value only when the key is not already present.
     */
    public function add(string $key, mixed $value, int $ttlSeconds): bool;

    /**
     * Removes a cached value by key.
     */
    public function forget(string $key): void;

    /**
     * Removes a cached value only when the stored value matches the expected value.
     */
    public function forgetIfValue(string $key, mixed $value): bool;

    /**
     * Clears all entries owned by the driver.
     */
    public function clear(): void;
}
