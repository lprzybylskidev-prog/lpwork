<?php
declare(strict_types=1);

use LPwork\Environment\Env;

/** @var Env $env */

/**
 * Error log settings and drivers.
 * driver: storage backend (file, redis, database).
 * level: reserved for future severity filtering; all exceptions are logged as error.
 * file.*: settings for file backend (rotation mode and directory).
 * database.*: settings for DB backend (connection/table).
 * redis.*: settings for Redis backend (connection/prefix/TTL/list limit).
 * response.*: propagation of error identifiers to clients.
 */
return [
    // Active error log driver.
    'driver' => $env->getString('ERROR_LOG_DRIVER', 'file'),
    // Logical level for filtering (reserved, currently unused).
    'level' => $env->getString('ERROR_LOG_LEVEL', 'error'),
    'file' => [
        // Rotation mode: single, daily, or monthly.
        'mode' => $env->getString('ERROR_LOG_FILE_MODE', 'daily'),
        // Directory (relative to default filesystem root) where error log files are stored.
        'directory' => $env->getString('ERROR_LOG_FILE_DIRECTORY', 'errors'),
    ],
    'database' => [
        // Database connection name for logging errors (default recommended).
        'connection' => $env->getString('ERROR_LOG_DB_CONNECTION', 'default'),
        // Table name for storing error log entries.
        'table' => $env->getString('ERROR_LOG_DB_TABLE', 'errors'),
    ],
    'redis' => [
        // Redis connection name dedicated to error logging.
        'connection' => $env->getString('ERROR_LOG_REDIS_CONNECTION', 'default'),
        // Key prefix/list name used for error entries.
        'prefix' => $env->getString('ERROR_LOG_REDIS_PREFIX', 'errors:'),
        // TTL in seconds for stored entries (0 disables expiry).
        'ttl' => $env->getInt('ERROR_LOG_REDIS_TTL', 0),
        // Maximum list length; 0 disables trimming.
        'max_entries' => $env->getInt('ERROR_LOG_REDIS_MAX_ENTRIES', 1000),
    ],
    'response' => [
        // Whether to add the error identifier to response headers.
        'expose_header' => $env->getBool('ERROR_LOG_RESPONSE_EXPOSE_HEADER', true),
        // Header name carrying the error identifier when exposed.
        'header_name' => $env->getString('ERROR_LOG_RESPONSE_HEADER_NAME', 'X-Error-Id'),
        // Whether to include error_id field in API JSON responses.
        'expose_api_payload' => $env->getBool('ERROR_LOG_RESPONSE_EXPOSE_API_PAYLOAD', true),
    ],
];
