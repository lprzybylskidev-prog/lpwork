<?php

declare(strict_types=1);

namespace Tests\support\view;

use LPWork\Cache\Contracts\CacheDriver;

final class TrackingCacheDriver implements CacheDriver
{
    /**
     * @var array<string, mixed>
     */
    private array $values = [];

    public int $gets = 0;

    public int $puts = 0;

    public function get(string $key, mixed $default = null): mixed
    {
        $this->gets++;

        return $this->values[$key] ?? $default;
    }

    public function put(string $key, mixed $value, ?int $ttlSeconds = null): void
    {
        $this->puts++;
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
}
