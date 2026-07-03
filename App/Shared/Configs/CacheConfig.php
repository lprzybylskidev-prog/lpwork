<?php

declare(strict_types=1);

namespace App\Shared\Configs;

use LPWork\Config\Contracts\ConfigDefinition;
use LPWork\Environment\Environment;
use LPWork\Shared\Exceptions\SingletonInstanceException;

/**
 * Configures named cache stores used by framework features and application code.
 */
final class CacheConfig implements ConfigDefinition
{
    public function key(): string
    {
        return 'cache';
    }

    /**
     * @return array<array-key, mixed>
     */
    public function values(): array
    {
        $default = $this->env('CACHE_STORE', 'framework');
        $stores = [
            'framework' => $this->store('framework'),
            'views' => $this->store('views'),
        ];

        foreach ($this->configuredStoreNames($default) as $store) {
            $stores[$store] = $this->store($store);
        }

        return [
            // CACHE_STORE chooses the default store; framework and views stores are always available.
            'default' => $default,
            'stores' => $stores,
        ];
    }

    /**
     * @return list<string>
     */
    private function configuredStoreNames(string $default): array
    {
        return array_values(array_unique(array_filter([
            $default,
            $this->env('THROTTLE_CACHE_STORE', ''),
            $this->env('LOCK_STORE', ''),
            $this->env('SESSION_CACHE_STORE', ''),
        ], static fn(string $store): bool => $store !== '')));
    }

    /**
     * @return array<array-key, mixed>
     */
    private function store(string $store): array
    {
        return match ($store) {
            // Shared Redis-backed store for cache, throttle, locks, or sessions when configured.
            'redis' => [
                'driver' => 'redis',
                'host' => $this->env('CACHE_REDIS_HOST', '127.0.0.1'),
                'port' => (int) $this->env('CACHE_REDIS_PORT', '6379'),
                'password' => $this->env('CACHE_REDIS_PASSWORD', ''),
                'database' => (int) $this->env('CACHE_REDIS_DATABASE', '0'),
                'timeout_seconds' => (float) $this->env('CACHE_REDIS_TIMEOUT_SECONDS', '2.5'),
                'prefix' => $this->env('CACHE_REDIS_PREFIX', ''),
            ],
            // Stores cache entries in the configured database connection and cache_entries table.
            'database' => [
                'driver' => 'database',
                'connection' => $this->env('CACHE_DATABASE_CONNECTION', ''),
                'table' => $this->env('CACHE_DATABASE_TABLE', 'cache_entries'),
            ],
            // Uses the APCu PHP extension and is process-local.
            'apcu' => [
                'driver' => 'apcu',
                'prefix' => $this->env('CACHE_APCU_PREFIX', 'lpwork:'),
            ],
            // View rendering cache; keep this file-backed unless the view subsystem is intentionally reconfigured.
            'views' => [
                'driver' => 'file',
                'disk' => 'local',
                'path' => 'framework/views',
            ],
            // Default file-backed cache under the local storage disk.
            default => [
                'driver' => 'file',
                'disk' => 'local',
                'path' => 'framework/cache',
            ],
        };
    }

    private function env(string $key, string $default): string
    {
        try {
            return Environment::get($key, $default);
        } catch (SingletonInstanceException) {
            return $default;
        }
    }
}
