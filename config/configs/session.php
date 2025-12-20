<?php
declare(strict_types=1);

use LPwork\Environment\Env;

/** @var Env $env */

return [
    /**
     * Session base settings.
     */
    "driver" => $env->getString("SESSION_DRIVER", "php"), // Active session driver: php, redis, database, filesystem
    "lifetime" => $env->getInt("SESSION_LIFETIME", 7200), // Lifetime in seconds
    /**
     * Session cookie parameters.
     */
    "cookie" => [
        "name" => $env->getString("SESSION_COOKIE_NAME", "LPWORKSESSID"), // Cookie name
        "path" => $env->getString("SESSION_COOKIE_PATH", "/"), // Cookie path
        "domain" => $env->getString("SESSION_COOKIE_DOMAIN", ""), // Cookie domain
        "secure" => $env->getBool("SESSION_COOKIE_SECURE", false), // Force secure flag; HTTPS requests always enforce secure
        "http_only" => $env->getBool("SESSION_COOKIE_HTTP_ONLY", true), // HttpOnly flag
        "same_site" => $env->getString("SESSION_COOKIE_SAMESITE", "lax"), // SameSite value: lax, strict or none
    ],
    /**
     * Driver-specific configuration.
     */
    "drivers" => [
        "php" => [
            "name" => $env->getString("SESSION_PHP_NAME", "LPWORKSESSID"), // Native session name
        ],
        "redis" => [
            "connection" => $env->getString(
                "SESSION_REDIS_CONNECTION",
                "default",
            ), // Redis connection name
            "prefix" => $env->getString("SESSION_REDIS_PREFIX", "session:"), // Redis key prefix
        ],
        "database" => [
            "connection" => $env->getString("SESSION_DB_CONNECTION", "default"), // Database connection name (must be default)
            "table" => $env->getString("SESSION_DB_TABLE", "sessions"), // Sessions table name
        ],
        "filesystem" => [
            "disk" => $env->getString("SESSION_FILESYSTEM_DISK", "local"), // Filesystem disk name
            "path" => $env->getString("SESSION_FILESYSTEM_PATH", "sessions"), // Directory on disk for session files
        ],
    ],
];
