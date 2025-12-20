<?php
declare(strict_types=1);

use LPwork\Environment\Env;

/** @var Env $env */

return [
    "default_connection" => "default",
    "connections" => [
        "default" => [
            "scheme" => $env->getString("REDIS_SCHEME", "tcp"),
            "host" => $env->getString("REDIS_HOST", "127.0.0.1"),
            "port" => $env->getInt("REDIS_PORT", 6379),
            "database" => $env->getInt("REDIS_DB", 0),
            "username" => $env->getString("REDIS_USERNAME", ""),
            "password" => $env->getString("REDIS_PASSWORD", ""),
            "prefix" => $env->getString("REDIS_PREFIX", ""),
        ],
        // Add more named connections here, e.g. "cache", "session".
    ],
];
