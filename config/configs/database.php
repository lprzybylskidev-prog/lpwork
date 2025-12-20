<?php
declare(strict_types=1);

use LPwork\Environment\Env;

/** @var Env $env */

return [
    "default_connection" => "default",
    "connections" => [
        "default" => [
            "driver" => $env->getString("DB_DRIVER", "pdo_mysql"),
            "host" => $env->getString("DB_HOST", "127.0.0.1"),
            "port" => $env->getInt("DB_PORT", 3306),
            "database" => $env->getString("DB_NAME", "lpwork"),
            "username" => $env->getString("DB_USER", "root"),
            "password" => $env->getString("DB_PASSWORD", ""),
            "charset" => $env->getString("DB_CHARSET", "utf8mb4"),
            "url" => $env->getString("DB_URL", ""),
            "path" => $env->getString("DB_PATH", ""),
        ],
        // Additional named connections can be added here.
    ],
];
