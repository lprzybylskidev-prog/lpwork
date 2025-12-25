<?php
declare(strict_types=1);

/** @var \LPwork\Environment\Env $env */

/**
 * Application logging configuration (PSR-3 via Monolog).
 * default_channel: channel used when no channel is specified.
 * channels.*: named channels with driver-specific settings.
 * Supported drivers:
 * - stderr (php://stderr)
 * - single (file path)
 * - redis (Predis connection, list key/prefix)
 * - database (PDO via Doctrine connection, user-provided table)
 */
return [
    // Name of the default logging channel.
    'default_channel' => $env->getString('LOG_DEFAULT_CHANNEL', 'stderr'),
    'channels' => [
        'stderr' => [
            // Driver type (stderr outputs to php://stderr).
            'driver' => 'stderr',
            // Minimum log level for this channel.
            'level' => $env->getString('LOG_STDERR_LEVEL', 'debug'),
            // Whether logs bubble to further handlers (kept true for single handler setups).
            'bubble' => true,
        ],
        'file' => [
            // Driver type (single file).
            'driver' => 'single',
            // File path for log storage.
            'path' => $env->getString(
                'LOG_FILE_PATH',
                \dirname(__DIR__, 2) . '/storage/logs/app.log',
            ),
            // Minimum log level for this channel.
            'level' => $env->getString('LOG_FILE_LEVEL', 'info'),
            // Whether logs bubble to further handlers.
            'bubble' => true,
        ],
        'redis' => [
            // Driver type (Redis list).
            'driver' => 'redis',
            // Redis connection name from Redis config.
            'connection' => $env->getString('LOG_REDIS_CONNECTION', 'default'),
            // Key/list name for log entries (prefix allowed).
            'key' => $env->getString('LOG_REDIS_KEY', 'logs'),
            // Minimum log level for this channel.
            'level' => $env->getString('LOG_REDIS_LEVEL', 'warning'),
            // Whether logs bubble to further handlers.
            'bubble' => true,
        ],
        'database' => [
            // Driver type (database table). Table must exist (no auto-migration here).
            'driver' => 'database',
            // Database connection name from DB config.
            'connection' => $env->getString('LOG_DB_CONNECTION', 'default'),
            // Table name for log entries (must exist with expected columns).
            'table' => $env->getString('LOG_DB_TABLE', 'logs'),
            // Minimum log level for this channel.
            'level' => $env->getString('LOG_DB_LEVEL', 'error'),
            // Whether logs bubble to further handlers.
            'bubble' => true,
        ],
    ],
];
