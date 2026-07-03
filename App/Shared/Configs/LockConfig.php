<?php

declare(strict_types=1);

namespace App\Shared\Configs;

use LPWork\Config\Contracts\ConfigDefinition;
use LPWork\Environment\Environment;

/**
 * Configures the default distributed lock backend and lock TTL.
 */
final class LockConfig implements ConfigDefinition
{
    public function key(): string
    {
        return 'locks';
    }

    /**
     * @return array<array-key, mixed>
     */
    public function values(): array
    {
        $driver = Environment::get('LOCK_DRIVER', 'cache');

        return [
            // Supported built-in drivers are cache, redis, and database.
            'driver' => $driver,
            'ttl_seconds' => (int) Environment::get('LOCK_TTL_SECONDS', '900'),
            ...$this->driver($driver),
        ];
    }

    /**
     * @return array<array-key, mixed>
     */
    private function driver(string $driver): array
    {
        return match ($driver) {
            // Redis locks require a reachable Redis server and keep lock state outside PHP workers.
            'redis' => [
                'host' => Environment::get('LOCK_REDIS_HOST', '127.0.0.1'),
                'port' => (int) Environment::get('LOCK_REDIS_PORT', '6379'),
                'password' => Environment::get('LOCK_REDIS_PASSWORD', ''),
                'database' => (int) Environment::get('LOCK_REDIS_DATABASE', '0'),
                'timeout_seconds' => (float) Environment::get('LOCK_REDIS_TIMEOUT_SECONDS', '2.5'),
                'prefix' => Environment::get('LOCK_REDIS_PREFIX', ''),
            ],
            // Database locks require the framework locks table migration.
            'database' => [
                'connection' => Environment::get('LOCK_DATABASE_CONNECTION', ''),
                'table' => Environment::get('LOCK_DATABASE_TABLE', 'locks'),
            ],
            // Cache locks use the named cache store selected by LOCK_STORE or CACHE_STORE.
            default => [
                'store' => Environment::get('LOCK_STORE', Environment::get('CACHE_STORE', 'framework')),
            ],
        };
    }
}
