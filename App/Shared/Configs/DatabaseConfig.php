<?php

declare(strict_types=1);

namespace App\Shared\Configs;

use LPWork\Config\Contracts\ConfigDefinition;
use LPWork\Config\Contracts\EnvironmentRequirementProvider;
use LPWork\Config\EnvironmentRequirement;
use LPWork\Environment\Environment;

/**
 * Selects the default database connection and optional query logging channel.
 */
final class DatabaseConfig implements ConfigDefinition, EnvironmentRequirementProvider
{
    public function key(): string
    {
        return 'database';
    }

    /**
     * @return array<array-key, mixed>
     */
    public function values(): array
    {
        $connection = Environment::getString('DB_CONNECTION');

        return [
            // Supported built-in connections are mysql, pgsql, and sqlite.
            'default' => $connection,
            'connections' => [
                $connection => $this->connection($connection),
            ],
            // Query logging is useful in development and diagnostics; keep it disabled or carefully scoped in production.
            'logging' => [
                'enabled' => Environment::getBool('DB_LOG_QUERIES'),
                'channel' => Environment::getString('DB_LOG_CHANNEL'),
                'level' => Environment::getString('DB_LOG_LEVEL'),
            ],
        ];
    }

    /**
     * @return array<array-key, mixed>
     */
    private function connection(string $connection): array
    {
        return match ($connection) {
            // Requires a reachable MySQL-compatible server and the pdo_mysql extension.
            'mysql' => [
                'driver' => 'mysql',
                'host' => Environment::getString('DB_MYSQL_HOST'),
                'port' => Environment::getInt('DB_MYSQL_PORT'),
                'database' => Environment::getString('DB_MYSQL_DATABASE'),
                'username' => Environment::getString('DB_MYSQL_USERNAME'),
                'password' => Environment::getString('DB_MYSQL_PASSWORD'),
                'charset' => Environment::getString('DB_MYSQL_CHARSET'),
            ],
            // Requires a reachable PostgreSQL server and the pdo_pgsql extension.
            'pgsql' => [
                'driver' => 'pgsql',
                'host' => Environment::getString('DB_PGSQL_HOST'),
                'port' => Environment::getInt('DB_PGSQL_PORT'),
                'database' => Environment::getString('DB_PGSQL_DATABASE'),
                'username' => Environment::getString('DB_PGSQL_USERNAME'),
                'password' => Environment::getString('DB_PGSQL_PASSWORD'),
                'charset' => Environment::getString('DB_PGSQL_CHARSET'),
            ],
            // Uses a local SQLite database path, commonly storage/database.sqlite.
            'sqlite' => [
                'driver' => 'sqlite',
                'database' => Environment::getString('DB_SQLITE_DATABASE'),
            ],
            default => [
                'driver' => $connection,
            ],
        };
    }

    /**
     * @return list<EnvironmentRequirement>
     */
    public function environmentRequirements(): array
    {
        return [
            EnvironmentRequirement::nonEmptyString('DB_CONNECTION'),
            EnvironmentRequirement::bool('DB_LOG_QUERIES'),
            EnvironmentRequirement::nonEmptyString('DB_LOG_CHANNEL'),
            EnvironmentRequirement::nonEmptyString('DB_LOG_LEVEL'),
            EnvironmentRequirement::nonEmptyString('DB_MYSQL_HOST')->when('DB_CONNECTION', 'mysql'),
            EnvironmentRequirement::int('DB_MYSQL_PORT')->when('DB_CONNECTION', 'mysql'),
            EnvironmentRequirement::nonEmptyString('DB_MYSQL_DATABASE')->when('DB_CONNECTION', 'mysql'),
            EnvironmentRequirement::nonEmptyString('DB_MYSQL_USERNAME')->when('DB_CONNECTION', 'mysql'),
            EnvironmentRequirement::string('DB_MYSQL_PASSWORD')->when('DB_CONNECTION', 'mysql'),
            EnvironmentRequirement::nonEmptyString('DB_MYSQL_CHARSET')->when('DB_CONNECTION', 'mysql'),
            EnvironmentRequirement::nonEmptyString('DB_PGSQL_HOST')->when('DB_CONNECTION', 'pgsql'),
            EnvironmentRequirement::int('DB_PGSQL_PORT')->when('DB_CONNECTION', 'pgsql'),
            EnvironmentRequirement::nonEmptyString('DB_PGSQL_DATABASE')->when('DB_CONNECTION', 'pgsql'),
            EnvironmentRequirement::nonEmptyString('DB_PGSQL_USERNAME')->when('DB_CONNECTION', 'pgsql'),
            EnvironmentRequirement::string('DB_PGSQL_PASSWORD')->when('DB_CONNECTION', 'pgsql'),
            EnvironmentRequirement::nonEmptyString('DB_PGSQL_CHARSET')->when('DB_CONNECTION', 'pgsql'),
            EnvironmentRequirement::nonEmptyString('DB_SQLITE_DATABASE')->when('DB_CONNECTION', 'sqlite'),
        ];
    }
}
