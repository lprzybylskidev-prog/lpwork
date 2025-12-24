<?php
declare(strict_types=1);

use LPwork\Environment\Env;

/** @var Env $env */

/**
 * Queue configuration.
 * default_queue: name of the queue used when none specified.
 * queues: named queues with driver-specific settings (redis/database/filesystem).
 * retry: global retry/backoff settings for queued jobs.
 */
return [
    // Name of the default queue.
    'default_queue' => $env->getString('QUEUE_DEFAULT', 'default'),
    'queues' => [
        'default' => [
            // Queue driver: redis, database, filesystem.
            'driver' => $env->getString('QUEUE_DEFAULT_DRIVER', 'redis'),
            // Optional serializer identifier (json by default).
            'serializer' => $env->getString('QUEUE_DEFAULT_SERIALIZER', 'json'),
            // Connection/bucket name for the selected driver.
            // Redis: Redis connection name; Database: DB connection name; Filesystem: disk name or "local".
            'connection' => $env->getString('QUEUE_DEFAULT_CONNECTION', 'default'),
            'redis' => [
                // Redis mode: list (LPUSH/BRPOP) or stream (XADD/XREAD).
                'mode' => $env->getString('QUEUE_DEFAULT_REDIS_MODE', 'list'),
                // Key for the queue.
                'key' => $env->getString('QUEUE_DEFAULT_REDIS_KEY', 'queues:default'),
                // Consumer group (stream mode only).
                'group' => $env->getString('QUEUE_DEFAULT_REDIS_GROUP', 'lpwork'),
                // Consumer name (stream mode only).
                'consumer' => $env->getString('QUEUE_DEFAULT_REDIS_CONSUMER', 'worker'),
                // Block timeout in seconds for stream reads.
                'block_seconds' => $env->getInt('QUEUE_DEFAULT_REDIS_BLOCK', 5),
            ],
            'database' => [
                // Table name storing queued jobs.
                'table' => $env->getString('QUEUE_DEFAULT_DB_TABLE', 'queue_jobs'),
            ],
            'filesystem' => [
                // Directory for queue job files.
                'path' => $env->getString(
                    'QUEUE_DEFAULT_FS_PATH',
                    \dirname(__DIR__, 2) . '/storage/queue',
                ),
            ],
        ],
    ],
    'retry' => [
        // Enable or disable queue job retry handling (true/false).
        'enabled' => $env->getBool('QUEUE_RETRY_ENABLED', true),
        // Maximum number of retries before giving up.
        'max_retries' => $env->getInt('QUEUE_RETRY_MAX_RETRIES', 3),
        // Initial delay before first retry in milliseconds.
        'delay_ms' => $env->getInt('QUEUE_RETRY_DELAY_MS', 1000),
        // Backoff multiplier applied to each subsequent retry (e.g. 2.0 doubles delay).
        'multiplier' => $env->getFloat('QUEUE_RETRY_MULTIPLIER', 2.0),
        // Maximum delay between retries in milliseconds (0 = no cap).
        'max_delay_ms' => $env->getInt('QUEUE_RETRY_MAX_DELAY_MS', 0),
    ],
];
