<?php
declare(strict_types=1);

namespace LPwork\Database;

use LPwork\Database\Contract\DatabaseConnectionInterface;
use LPwork\Database\Exception\DatabaseConnectionNotFoundException;

/**
 * Manages named database connections.
 */
class DatabaseConnectionManager
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $configurations;

    /**
     * @var array<string, DatabaseConnectionInterface>
     */
    private array $connections = [];

    /**
     * @var string
     */
    private string $defaultConnection;

    /**
     * @param array<string, array<string, mixed>> $configurations
     * @param string $defaultConnection
     */
    public function __construct(
        array $configurations,
        string $defaultConnection = "default",
    ) {
        $this->configurations = $configurations;
        $this->defaultConnection = $defaultConnection;
    }

    /**
     * Returns a connection by name.
     *
     * @param string|null $name
     *
     * @return DatabaseConnectionInterface
     */
    public function get(?string $name = null): DatabaseConnectionInterface
    {
        $connectionName = $name ?? $this->defaultConnection;

        if (isset($this->connections[$connectionName])) {
            return $this->connections[$connectionName];
        }

        if (!isset($this->configurations[$connectionName])) {
            throw new DatabaseConnectionNotFoundException(
                \sprintf(
                    'Database connection "%s" is not configured.',
                    $connectionName,
                ),
            );
        }

        $config = new DatabaseConfig($this->configurations[$connectionName]);
        $connection = new DoctrineDatabaseConnection($config);

        $this->connections[$connectionName] = $connection;

        return $connection;
    }

    /**
     * Returns configured connection names.
     *
     * @return array<int, string>
     */
    public function getConnectionNames(): array
    {
        return \array_keys($this->configurations);
    }

    /**
     * Returns the default connection name.
     *
     * @return string
     */
    public function getDefaultConnectionName(): string
    {
        return $this->defaultConnection;
    }
}
