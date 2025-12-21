<?php
declare(strict_types=1);

use LPwork\Environment\Env;

/** @var Env $env */

$timezone = \trim($env->getString("APP_TIMEZONE", "UTC"));

if ($timezone === "") {
    $timezone = "UTC";
}

return [
    /**
     * Database connections consumed by the framework (Doctrine DBAL).
     * default_connection: name used when no connection is specified.
     * connections.*.driver: DBAL driver (pdo_sqlite, pdo_mysql, pdo_pgsql, pdo_sqlsrv).
     * connections.*.host/port/database/username/password: connection credentials for networked drivers.
     * connections.*.path: SQLite path or DSN/host string for other drivers.
     * connections.*.charset: character set for supported drivers (e.g. utf8mb4).
     * connections.*.timezone: session timezone applied on connect (APP_TIMEZONE, fallback UTC). SQLite ignores it; other drivers run SET TIME ZONE/session equivalent.
     */
    // Name of the default database connection.
    "default_connection" => "default",
    "connections" => [
        "default" => [
            // DBAL driver identifier.
            "driver" => $env->getString("DB_DRIVER", "pdo_sqlite"),
            // Hostname for networked drivers (ignored for SQLite).
            "host" => $env->getString("DB_HOST", "127.0.0.1"),
            // Port for networked drivers (ignored for SQLite).
            "port" => $env->getInt("DB_PORT", 3306),
            // Database/schema name for networked drivers.
            "database" => $env->getString("DB_DATABASE", ""),
            // Username for networked drivers.
            "username" => $env->getString("DB_USERNAME", ""),
            // Password for networked drivers.
            "password" => $env->getString("DB_PASSWORD", ""),
            // SQLite file path or DSN/host for other drivers.
            "path" => $env->getString(
                "DB_PATH",
                \dirname(__DIR__, 2) . "/storage/db.sqlite3",
            ),
            // Character set for drivers that support it.
            "charset" => $env->getString("DB_CHARSET", "utf8mb4"),
            // Session timezone applied after connect (APP_TIMEZONE with UTC fallback, aliases allowed).
            "timezone" => $timezone,
        ],
        // Additional named connections can be added here.
    ],
];
