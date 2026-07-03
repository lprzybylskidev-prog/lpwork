<?php

declare(strict_types=1);

namespace App\Shared\Configs;

use LPWork\Config\Contracts\ConfigDefinition;
use LPWork\Environment\Environment;

/**
 * Configures event broadcasting through log, Redis, or Pusher-compatible transports.
 */
final class BroadcastingConfig implements ConfigDefinition
{
    public function key(): string
    {
        return 'broadcasting';
    }

    /**
     * @return array<array-key, mixed>
     */
    public function values(): array
    {
        $connection = Environment::get('BROADCAST_CONNECTION', 'log');

        return [
            // BROADCAST_CONNECTION supports log, redis, and pusher-compatible drivers.
            'default' => $connection,
            'connections' => [
                $connection => $this->connection($connection),
            ],
            // Log broadcasting records broadcast attempts to the configured logging channel.
            'logging' => [
                'channel' => Environment::get('BROADCAST_LOG_CHANNEL', 'app'),
            ],
        ];
    }

    /**
     * @return array<array-key, mixed>
     */
    private function connection(string $connection): array
    {
        return match ($connection) {
            // Redis broadcasting requires a reachable Redis server.
            'redis' => [
                'driver' => 'redis',
                'host' => Environment::get('BROADCAST_REDIS_HOST', '127.0.0.1'),
                'port' => (int) Environment::get('BROADCAST_REDIS_PORT', '6379'),
                'password' => Environment::get('BROADCAST_REDIS_PASSWORD', ''),
                'database' => (int) Environment::get('BROADCAST_REDIS_DATABASE', '0'),
                'timeout_seconds' => (float) Environment::get('BROADCAST_REDIS_TIMEOUT_SECONDS', '2.5'),
                'prefix' => Environment::get('BROADCAST_REDIS_PREFIX', ''),
            ],
            // Pusher-compatible broadcasting can target Soketi or hosted Pusher.
            'pusher' => [
                'driver' => 'pusher',
                'app_id' => Environment::get('PUSHER_APP_ID', ''),
                'key' => Environment::get('PUSHER_APP_KEY', ''),
                'secret' => Environment::get('PUSHER_APP_SECRET', ''),
                'endpoint' => Environment::get('PUSHER_ENDPOINT', 'http://127.0.0.1:6001'),
            ],
            default => [
                'driver' => $connection,
            ],
        };
    }
}
