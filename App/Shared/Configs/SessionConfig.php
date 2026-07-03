<?php

declare(strict_types=1);

namespace App\Shared\Configs;

use LPWork\Config\Contracts\ConfigDefinition;
use LPWork\Config\Contracts\EnvironmentRequirementProvider;
use LPWork\Config\EnvironmentRequirement;
use LPWork\Environment\Environment;

/**
 * Selects the session driver and cookie attributes used by HTTP requests.
 */
final class SessionConfig implements ConfigDefinition, EnvironmentRequirementProvider
{
    public function key(): string
    {
        return 'session';
    }

    /**
     * @return array<array-key, mixed>
     */
    public function values(): array
    {
        $driver = Environment::getString('SESSION_DRIVER');

        return [
            // Supported built-in drivers are memory, cache, database, redis, and php.
            'default' => $driver,
            'drivers' => [
                $driver => $this->driver($driver),
            ],
        ];
    }

    /**
     * @return array<array-key, mixed>
     */
    private function driver(string $driver): array
    {
        return match ($driver) {
            // Memory sessions are process-local and suitable for tests or short-lived flows only.
            'memory' => [
                'driver' => 'memory',
            ],
            // Cache sessions reuse the named cache store selected by SESSION_CACHE_STORE or CACHE_STORE.
            'cache' => [
                ...$this->cookieDriver($driver),
                'store' => Environment::get('SESSION_CACHE_STORE', Environment::get('CACHE_STORE', 'framework')),
            ],
            // Database sessions require the sessions table migration and a configured database connection.
            'database' => [
                ...$this->cookieDriver($driver),
                'connection' => Environment::get('SESSION_DATABASE_CONNECTION', ''),
                'table' => Environment::get('SESSION_DATABASE_TABLE', 'sessions'),
            ],
            // Redis sessions use a dedicated Redis connection defined by SESSION_REDIS_* values.
            'redis' => [
                ...$this->cookieDriver($driver),
                'host' => Environment::get('SESSION_REDIS_HOST', '127.0.0.1'),
                'port' => (int) Environment::get('SESSION_REDIS_PORT', '6379'),
                'password' => Environment::get('SESSION_REDIS_PASSWORD', ''),
                'database' => (int) Environment::get('SESSION_REDIS_DATABASE', '0'),
                'timeout_seconds' => (float) Environment::get('SESSION_REDIS_TIMEOUT_SECONDS', '2.5'),
                'prefix' => Environment::get('SESSION_REDIS_PREFIX', ''),
            ],
            // PHP sessions delegate storage to PHP's configured session handler.
            'php' => [
                ...$this->cookieDriver('php'),
                'use_strict_mode' => Environment::getBool('SESSION_USE_STRICT_MODE'),
            ],
            default => [
                ...$this->cookieDriver($driver),
                'use_strict_mode' => Environment::getBool('SESSION_USE_STRICT_MODE'),
            ],
        };
    }

    /**
     * @return array<array-key, mixed>
     */
    private function cookieDriver(string $driver): array
    {
        return [
            // Cookie options apply to every persistent session driver.
            'driver' => $driver,
            'name' => Environment::getString('SESSION_NAME'),
            'lifetime' => Environment::getInt('SESSION_LIFETIME'),
            'path' => Environment::getString('SESSION_PATH'),
            'domain' => Environment::getString('SESSION_DOMAIN'),
            'secure' => Environment::getBool('SESSION_SECURE'),
            'http_only' => Environment::getBool('SESSION_HTTP_ONLY'),
            'same_site' => Environment::getString('SESSION_SAME_SITE'),
        ];
    }

    /**
     * @return list<EnvironmentRequirement>
     */
    public function environmentRequirements(): array
    {
        return [
            EnvironmentRequirement::nonEmptyString('SESSION_DRIVER'),
            EnvironmentRequirement::bool('SESSION_USE_STRICT_MODE')->when('SESSION_DRIVER', 'php'),
            EnvironmentRequirement::nonEmptyString('SESSION_NAME')->when('SESSION_DRIVER', 'cache'),
            EnvironmentRequirement::int('SESSION_LIFETIME')->when('SESSION_DRIVER', 'cache'),
            EnvironmentRequirement::nonEmptyString('SESSION_PATH')->when('SESSION_DRIVER', 'cache'),
            EnvironmentRequirement::string('SESSION_DOMAIN')->when('SESSION_DRIVER', 'cache'),
            EnvironmentRequirement::bool('SESSION_SECURE')->when('SESSION_DRIVER', 'cache'),
            EnvironmentRequirement::bool('SESSION_HTTP_ONLY')->when('SESSION_DRIVER', 'cache'),
            EnvironmentRequirement::nonEmptyString('SESSION_SAME_SITE')->when('SESSION_DRIVER', 'cache'),
            EnvironmentRequirement::nonEmptyString('SESSION_NAME')->when('SESSION_DRIVER', 'database'),
            EnvironmentRequirement::int('SESSION_LIFETIME')->when('SESSION_DRIVER', 'database'),
            EnvironmentRequirement::nonEmptyString('SESSION_PATH')->when('SESSION_DRIVER', 'database'),
            EnvironmentRequirement::string('SESSION_DOMAIN')->when('SESSION_DRIVER', 'database'),
            EnvironmentRequirement::bool('SESSION_SECURE')->when('SESSION_DRIVER', 'database'),
            EnvironmentRequirement::bool('SESSION_HTTP_ONLY')->when('SESSION_DRIVER', 'database'),
            EnvironmentRequirement::nonEmptyString('SESSION_SAME_SITE')->when('SESSION_DRIVER', 'database'),
            EnvironmentRequirement::nonEmptyString('SESSION_NAME')->when('SESSION_DRIVER', 'redis'),
            EnvironmentRequirement::int('SESSION_LIFETIME')->when('SESSION_DRIVER', 'redis'),
            EnvironmentRequirement::nonEmptyString('SESSION_PATH')->when('SESSION_DRIVER', 'redis'),
            EnvironmentRequirement::string('SESSION_DOMAIN')->when('SESSION_DRIVER', 'redis'),
            EnvironmentRequirement::bool('SESSION_SECURE')->when('SESSION_DRIVER', 'redis'),
            EnvironmentRequirement::bool('SESSION_HTTP_ONLY')->when('SESSION_DRIVER', 'redis'),
            EnvironmentRequirement::nonEmptyString('SESSION_SAME_SITE')->when('SESSION_DRIVER', 'redis'),
            EnvironmentRequirement::nonEmptyString('SESSION_NAME')->when('SESSION_DRIVER', 'php'),
            EnvironmentRequirement::int('SESSION_LIFETIME')->when('SESSION_DRIVER', 'php'),
            EnvironmentRequirement::nonEmptyString('SESSION_PATH')->when('SESSION_DRIVER', 'php'),
            EnvironmentRequirement::string('SESSION_DOMAIN')->when('SESSION_DRIVER', 'php'),
            EnvironmentRequirement::bool('SESSION_SECURE')->when('SESSION_DRIVER', 'php'),
            EnvironmentRequirement::bool('SESSION_HTTP_ONLY')->when('SESSION_DRIVER', 'php'),
            EnvironmentRequirement::nonEmptyString('SESSION_SAME_SITE')->when('SESSION_DRIVER', 'php'),
        ];
    }
}
