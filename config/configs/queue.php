<?php
declare(strict_types=1);

use LPwork\Environment\Env;

/** @var Env $env */

return [
    /**
     * Queue configuration.
     * default_queue: name of the queue used when none specified.
     * queues: named queues with driver-specific settings (redis/database/filesystem).
     */
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
];
