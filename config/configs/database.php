<?php
declare(strict_types=1);

use LPwork\Environment\Env;

/** @var Env $env */

return [
    /**
     * Database connections consumed by the framework (Doctrine DBAL).
     * default_connection: name used when no connection is specified.
     * connections.*.driver: DBAL driver (pdo_sqlite, pdo_mysql, pdo_pgsql).
     * connections.*.path: SQLite path or DSN/host string for other drivers.
     * connections.*.charset: character set for supported drivers (e.g. utf8mb4).
     */
    // Name of the default database connection.
    "default_connection" => "default",
    "connections" => [
        "default" => [
            // DBAL driver identifier.
            "driver" => $env->getString("DB_DRIVER", "pdo_sqlite"),
            // SQLite file path or DSN/host for other drivers.
            "path" => $env->getString(
                "DB_PATH",
                \dirname(__DIR__, 2) . "/storage/db.sqlite3",
            ),
            // Character set for drivers that support it.
            "charset" => $env->getString("DB_CHARSET", "utf8mb4"),
        ],
        // Additional named connections can be added here.
    ],
];
