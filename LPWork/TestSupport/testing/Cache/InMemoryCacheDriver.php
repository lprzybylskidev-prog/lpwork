<?php

declare(strict_types=1);

namespace Tests\support\testing\Cache;

use LPWork\Cache\Contracts\CacheDriver;

final class InMemoryCacheDriver implements CacheDriver
{
    /**
     * @var array<string, mixed>
     */
    private array $values = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    public function put(string $key, mixed $value, ?int $ttlSeconds = null): void
    {
        $this->values[$key] = $value;
    }

    public function add(string $key, mixed $value, int $ttlSeconds): bool
    {
        if (array_key_exists($key, $this->values)) {
            return false;
        }

        $this->values[$key] = $value;

        return true;
    }

    public function forget(string $key): void
    {
        unset($this->values[$key]);
    }

    public function forgetIfValue(string $key, mixed $value): bool
    {
        if (($this->values[$key] ?? null) !== $value) {
            return false;
        }

        unset($this->values[$key]);

        return true;
    }

    public function clear(): void
    {
        $this->values = [];
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }
}
