<?php

declare(strict_types=1);

namespace LPWork\Cache\Drivers;

use APCUIterator;
use LPWork\Cache\Contracts\CacheDriver;
use LPWork\Cache\Exceptions\InvalidCacheKeyException;
use LPWork\Cache\Exceptions\InvalidCacheTtlException;
use LPWork\Shared\Exceptions\MissingPhpExtensionException;

/**
 * Represents the apcu cache driver framework component.
 */
final readonly class ApcuCacheDriver implements CacheDriver
{
    /**
     * Creates a new ApcuCacheDriver instance.
     */
    public function __construct(private string $prefix = '') {}

    /**
     * Returns the requested value from this component.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->assertAvailable();
        $success = false;
        $value = apcu_fetch($this->key($key), $success);

        return $success ? $value : $default;
    }

    /**
     * Stores or replaces a value in this component's backing store.
     */
    public function put(string $key, mixed $value, ?int $ttlSeconds = null): void
    {
        $this->assertAvailable();
        apcu_store($this->key($key), $value, $ttlSeconds ?? 0);
    }

    /**
     * Adds an item to this component's registry or backing store.
     */
    public function add(string $key, mixed $value, int $ttlSeconds): bool
    {
        $this->assertAvailable();
        $this->assertValidTtl($ttlSeconds);

        return apcu_add($this->key($key), $value, $ttlSeconds);
    }

    /**
     * Removes a value from this component's backing store.
     */
    public function forget(string $key): void
    {
        $this->assertAvailable();
        apcu_delete($this->key($key));
    }

    /**
     * Removes or clears forget if value.
     */
    public function forgetIfValue(string $key, mixed $value): bool
    {
        $this->assertAvailable();
        $cacheKey = $this->key($key);
        $success = false;
        $stored = apcu_fetch($cacheKey, $success);

        if (!$success || $stored !== $value) {
            return false;
        }

        return apcu_delete($cacheKey);
    }

    /**
     * Clears the state owned by this component.
     */
    public function clear(): void
    {
        $this->assertAvailable();
        $iterator = new APCUIterator('/^' . preg_quote($this->prefix, '/') . '/');

        foreach ($iterator as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $key = $entry['key'] ?? null;

            if (is_string($key)) {
                apcu_delete($key);
            }
        }
    }

    private function key(string $key): string
    {
        if ($key === '') {
            throw new InvalidCacheKeyException();
        }

        return $this->prefix . $key;
    }

    private function assertValidTtl(int $ttlSeconds): void
    {
        if ($ttlSeconds <= 0) {
            throw new InvalidCacheTtlException();
        }
    }

    private function assertAvailable(): void
    {
        if (!function_exists('apcu_fetch')) {
            throw new MissingPhpExtensionException('apcu', 'cache.apcu');
        }
    }
}
