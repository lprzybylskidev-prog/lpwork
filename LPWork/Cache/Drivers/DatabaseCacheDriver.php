<?php

declare(strict_types=1);

namespace LPWork\Cache\Drivers;

use LPWork\Cache\Contracts\CacheDriver;
use LPWork\Cache\Exceptions\InvalidCacheKeyException;
use LPWork\Cache\Exceptions\InvalidCacheTtlException;
use LPWork\Database\Contracts\Connection;
use LPWork\Database\SqlIdentifier;
use LPWork\Time\Contracts\Clock;
use LPWork\Time\SystemClock;

/**
 * Represents the database cache driver framework component.
 */
final readonly class DatabaseCacheDriver implements CacheDriver
{
    /**
     * Creates a new DatabaseCacheDriver instance.
     */
    public function __construct(
        private Connection $connection,
        string $table = 'cache_entries',
        private Clock $clock = new SystemClock(),
    ) {
        $this->table = SqlIdentifier::table($table);
    }

    private string $table;

    /**
     * Returns the requested value from this component.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $key = $this->key($key);
        $row = $this->connection->query(
            sprintf('select value, expires_at from %s where cache_key = ?', $this->table),
            [$key],
        )->first();

        if (!is_array($row)) {
            return $default;
        }

        $expiresAt = $row['expires_at'] ?? null;

        if (($expiresAt === null || is_int($expiresAt) || is_string($expiresAt)) && $expiresAt !== null && (int) $expiresAt <= $this->now()) {
            $this->forget($key);

            return $default;
        }

        $value = $row['value'] ?? null;

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

        $key = $this->key($key);
        $this->forget($key);
        $this->connection->statement(
            sprintf('insert into %s (cache_key, value, expires_at, updated_at) values (?, ?, ?, ?)', $this->table),
            [$key, serialize($value), $ttlSeconds === null ? null : $this->now() + $ttlSeconds, $this->now()],
        );
    }

    /**
     * Adds an item to this component's registry or backing store.
     */
    public function add(string $key, mixed $value, int $ttlSeconds): bool
    {
        $this->assertValidTtl($ttlSeconds);
        $key = $this->key($key);

        return $this->connection->transaction(function () use ($key, $value, $ttlSeconds): bool {
            $row = $this->connection->query(
                sprintf('select expires_at from %s where cache_key = ?', $this->table),
                [$key],
            )->first();

            if (is_array($row)) {
                $expiresAt = $row['expires_at'] ?? null;

                if ($expiresAt === null || $this->intValue($expiresAt) > $this->now()) {
                    return false;
                }

                $this->forget($key);
            }

            $this->connection->statement(
                sprintf('insert into %s (cache_key, value, expires_at, updated_at) values (?, ?, ?, ?)', $this->table),
                [$key, serialize($value), $this->now() + $ttlSeconds, $this->now()],
            );

            return true;
        });
    }

    /**
     * Removes a value from this component's backing store.
     */
    public function forget(string $key): void
    {
        $this->connection->statement(
            sprintf('delete from %s where cache_key = ?', $this->table),
            [$this->key($key)],
        );
    }

    /**
     * Removes or clears forget if value.
     */
    public function forgetIfValue(string $key, mixed $value): bool
    {
        $affected = $this->connection->statement(
            sprintf('delete from %s where cache_key = ? and value = ?', $this->table),
            [$this->key($key), serialize($value)],
        );

        return $affected > 0;
    }

    /**
     * Clears the state owned by this component.
     */
    public function clear(): void
    {
        $this->connection->statement(sprintf('delete from %s', $this->table));
    }

    private function key(string $key): string
    {
        if ($key === '') {
            throw new InvalidCacheKeyException();
        }

        return $key;
    }

    private function assertValidTtl(int $ttlSeconds): void
    {
        if ($ttlSeconds <= 0) {
            throw new InvalidCacheTtlException();
        }
    }

    private function now(): int
    {
        return $this->clock->now()->getTimestamp();
    }

    private function intValue(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^[+-]?\d+$/', $value) === 1) {
            return (int) $value;
        }

        return 0;
    }
}
