<?php

declare(strict_types=1);

namespace LPWork\Health\Checks;

use LPWork\Cache\CacheManager;
use LPWork\Health\Contracts\HealthCheck;
use LPWork\Health\HealthCheckResult;
use Throwable;

/**
 * Represents the cache health check framework component.
 */
final readonly class CacheHealthCheck implements HealthCheck
{
    /**
     * Creates a new CacheHealthCheck instance.
     */
    public function __construct(
        private CacheManager $cache,
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'cache';
    }

    /**
     * Performs the check operation.
     */
    public function check(): HealthCheckResult
    {
        $key = 'lpwork.health.' . bin2hex(random_bytes(8));
        $value = 'ok';
        $storeName = $this->cache->defaultStoreName();
        $driverName = $this->cache->storeDriverName($storeName);

        try {
            $store = $this->cache->default();
            $store->put($key, $value);
            $stored = $store->get($key);
            $store->forget($key);
        } catch (Throwable $throwable) {
            return HealthCheckResult::unhealthy(
                $this->name(),
                sprintf('Cache store [%s] using driver [%s] failed: %s.', $storeName, $driverName, $throwable::class),
            );
        }

        if ($stored !== $value) {
            return HealthCheckResult::unhealthy($this->name(), sprintf('Cache store [%s] using driver [%s] did not return the probe value.', $storeName, $driverName));
        }

        return HealthCheckResult::healthy($this->name(), sprintf('Cache store [%s] using driver [%s] is readable and writable.', $storeName, $driverName));
    }
}
