<?php

declare(strict_types=1);

namespace LPWork\Cache;

use LPWork\Cache\Contracts\CacheDriver;
use Throwable;

/**
 * Provides typed cache operations for one configured store while recording optional debug diagnostics.
 */
final readonly class CacheStore
{
    /**
     * Creates a cache store wrapper around a concrete cache driver.
     */
    public function __construct(
        public string $name,
        private CacheDriver $driver,
        private ?CacheDebugCollector $debugCollector = null,
    ) {}

    /**
     * Returns a cached value or the supplied default when the key is missing or expired.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $started = hrtime(true);

        try {
            $value = $this->driver->get($key, $default);
            $this->record('get', $key, $started, true, [
                'Value type' => get_debug_type($value),
            ]);

            return $value;
        } catch (Throwable $throwable) {
            $this->record('get', $key, $started, false, [
                'Exception' => $throwable::class,
            ]);

            throw $throwable;
        }
    }

    /**
     * Stores or replaces a cached value, optionally expiring it after the given number of seconds.
     */
    public function put(string $key, mixed $value, ?int $ttlSeconds = null): void
    {
        $started = hrtime(true);

        try {
            $this->driver->put($key, $value, $ttlSeconds);
            $this->record('put', $key, $started, true, [
                'TTL seconds' => $ttlSeconds,
                'Value type' => get_debug_type($value),
            ]);
        } catch (Throwable $throwable) {
            $this->record('put', $key, $started, false, [
                'Exception' => $throwable::class,
            ]);

            throw $throwable;
        }
    }

    /**
     * Stores a cached value only when the key is not already present.
     */
    public function add(string $key, mixed $value, int $ttlSeconds): bool
    {
        $started = hrtime(true);

        try {
            $stored = $this->driver->add($key, $value, $ttlSeconds);
            $this->record('add', $key, $started, true, [
                'Stored' => $stored,
                'TTL seconds' => $ttlSeconds,
                'Value type' => get_debug_type($value),
            ]);

            return $stored;
        } catch (Throwable $throwable) {
            $this->record('add', $key, $started, false, [
                'Exception' => $throwable::class,
            ]);

            throw $throwable;
        }
    }

    /**
     * Removes a cached value by key.
     */
    public function forget(string $key): void
    {
        $started = hrtime(true);

        try {
            $this->driver->forget($key);
            $this->record('forget', $key, $started, true);
        } catch (Throwable $throwable) {
            $this->record('forget', $key, $started, false, [
                'Exception' => $throwable::class,
            ]);

            throw $throwable;
        }
    }

    /**
     * Removes a cached value only when the stored value matches the expected value.
     */
    public function forgetIfValue(string $key, mixed $value): bool
    {
        $started = hrtime(true);

        try {
            $forgotten = $this->driver->forgetIfValue($key, $value);
            $this->record('forget_if_value', $key, $started, true, [
                'Forgotten' => $forgotten,
                'Expected type' => get_debug_type($value),
            ]);

            return $forgotten;
        } catch (Throwable $throwable) {
            $this->record('forget_if_value', $key, $started, false, [
                'Exception' => $throwable::class,
            ]);

            throw $throwable;
        }
    }

    /**
     * Clears all entries owned by this cache store.
     */
    public function clear(): void
    {
        $started = hrtime(true);

        try {
            $this->driver->clear();
            $this->record('clear', '*', $started, true);
        } catch (Throwable $throwable) {
            $this->record('clear', '*', $started, false, [
                'Exception' => $throwable::class,
            ]);

            throw $throwable;
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    private function record(string $operation, string $key, int $started, bool $successful, array $context = []): void
    {
        $this->debugCollector?->record(
            store: $this->name,
            operation: $operation,
            key: $key,
            durationMs: round((hrtime(true) - $started) / 1_000_000, 3),
            successful: $successful,
            context: $context,
        );
    }
}
