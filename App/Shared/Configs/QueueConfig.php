<?php

declare(strict_types=1);

namespace App\Shared\Configs;

use LPWork\Config\Contracts\ConfigDefinition;
use LPWork\Environment\Environment;

/**
 * Configures queue dispatch, retry behavior, retention windows, and the selected backend.
 */
final class QueueConfig implements ConfigDefinition
{
    public function key(): string
    {
        return 'queue';
    }

    /**
     * @return array<array-key, mixed>
     */
    public function values(): array
    {
        $connection = Environment::get('QUEUE_CONNECTION', 'sync');

        return [
            // QUEUE_CONNECTION supports sync, database, redis, and sqs.
            'default' => $connection,
            'queue' => Environment::get('QUEUE_NAME', 'default'),
            // Retry values are measured in seconds and apply to worker-managed jobs.
            'retry' => [
                'max_attempts' => (int) Environment::get('QUEUE_MAX_ATTEMPTS', '3'),
                'retry_after_seconds' => (int) Environment::get('QUEUE_RETRY_AFTER_SECONDS', '90'),
                'delay_seconds' => (int) Environment::get('QUEUE_RETRY_DELAY_SECONDS', '5'),
            ],
            // Retention controls how long completed and failed job records are kept.
            'retention' => [
                'completed_seconds' => (int) Environment::get('QUEUE_COMPLETED_RETENTION_SECONDS', '604800'),
                'failed_seconds' => (int) Environment::get('QUEUE_FAILED_RETENTION_SECONDS', '2592000'),
            ],
            'connections' => [
                $connection => $this->connection($connection),
            ],
        ];
    }

    /**
     * @return array<array-key, mixed>
     */
    private function connection(string $connection): array
    {
        return match ($connection) {
            // Database queues require the framework queue migration table.
            'database' => [
                'driver' => 'database',
                'connection' => Environment::get('QUEUE_DATABASE_CONNECTION', ''),
                'table' => Environment::get('QUEUE_DATABASE_TABLE', 'queue_jobs'),
            ],
            // Redis queues require a reachable Redis server.
            'redis' => [
                'driver' => 'redis',
                'host' => Environment::get('QUEUE_REDIS_HOST', '127.0.0.1'),
                'port' => (int) Environment::get('QUEUE_REDIS_PORT', '6379'),
                'password' => Environment::get('QUEUE_REDIS_PASSWORD', ''),
                'database' => (int) Environment::get('QUEUE_REDIS_DATABASE', '0'),
                'timeout_seconds' => (float) Environment::get('QUEUE_REDIS_TIMEOUT_SECONDS', '2.5'),
                'prefix' => Environment::get('QUEUE_REDIS_PREFIX', ''),
            ],
            // SQS queues require a queue URL and AWS-compatible credentials.
            'sqs' => [
                'driver' => 'sqs',
                'queue_url' => Environment::get('QUEUE_SQS_URL', ''),
                'region' => Environment::get('QUEUE_SQS_REGION', 'us-east-1'),
                'access_key' => Environment::get('QUEUE_SQS_ACCESS_KEY', ''),
                'secret_key' => Environment::get('QUEUE_SQS_SECRET_KEY', ''),
            ],
            // Sync queues execute work immediately in the current process.
            'sync' => [
                'driver' => 'sync',
            ],
            default => [
                'driver' => $connection,
            ],
        };
    }
}
