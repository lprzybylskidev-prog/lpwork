<?php
declare(strict_types=1);

use LPwork\Environment\Env;

/** @var Env $env */

return [
    "default_connection" => "default",
    "connections" => [
        "default" => [
            "driver" => $env->getString("DB_DRIVER", "pdo_sqlite"),
            "path" => $env->getString(
                "DB_PATH",
                \dirname(__DIR__, 2) . "/storage/db.sqlite3",
            ),
            "charset" => $env->getString("DB_CHARSET", "utf8mb4"),
        ],
        // Additional named connections can be added here.
    ],
];
