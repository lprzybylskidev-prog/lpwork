<?php
declare(strict_types=1);

use LPwork\Environment\Env;

/** @var Env $env */

return [
    /**
     * Session core settings.
     * driver: session storage backend (php, redis, database, filesystem).
     * lifetime: session lifetime in seconds.
     * cookie.*: cookie parameters applied to the session ID cookie.
     * drivers.*: backend-specific configuration values.
     */
    // Active session driver.
    "driver" => $env->getString("SESSION_DRIVER", "php"),
    // Session lifetime in seconds (cookie Max-Age / storage TTL).
    "lifetime" => $env->getInt("SESSION_LIFETIME", 7200),
    "cookie" => [
        // Cookie name carrying the session ID.
        "name" => $env->getString("SESSION_COOKIE_NAME", "LPWORKSESSID"),
        // Path scope for the session cookie.
        "path" => $env->getString("SESSION_COOKIE_PATH", "/"),
        // Domain scope for the session cookie; empty uses current host.
        "domain" => $env->getString("SESSION_COOKIE_DOMAIN", ""),
        // Secure flag; enforced automatically for HTTPS requests.
        "secure" => $env->getBool("SESSION_COOKIE_SECURE", false),
        // HttpOnly flag to hide cookie from JS.
        "http_only" => $env->getBool("SESSION_COOKIE_HTTP_ONLY", true),
        // SameSite policy: lax, strict, or none.
        "same_site" => $env->getString("SESSION_COOKIE_SAMESITE", "lax"),
    ],
    "drivers" => [
        "php" => [
            // Native PHP session name.
            "name" => $env->getString("SESSION_PHP_NAME", "LPWORKSESSID"),
        ],
        "redis" => [
            // Redis connection name used by the session driver.
            "connection" => $env->getString(
                "SESSION_REDIS_CONNECTION",
                "default",
            ),
            // Key prefix for session entries in Redis.
            "prefix" => $env->getString("SESSION_REDIS_PREFIX", "session:"),
        ],
        "database" => [
            // Database connection name (must match default DB connection).
            "connection" => $env->getString("SESSION_DB_CONNECTION", "default"),
            // Table name storing session rows.
            "table" => $env->getString("SESSION_DB_TABLE", "sessions"),
        ],
        "filesystem" => [
            // Filesystem disk name for session files.
            "disk" => $env->getString("SESSION_FILESYSTEM_DISK", "local"),
            // Directory path (on the disk) for session files.
            "path" => $env->getString("SESSION_FILESYSTEM_PATH", "sessions"),
        ],
    ],
];
