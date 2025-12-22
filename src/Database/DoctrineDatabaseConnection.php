<?php
declare(strict_types=1);

namespace LPwork\Database;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use LPwork\Database\Contract\DatabaseConnectionInterface;

/**
 * Doctrine DBAL based database connection.
 */
class DoctrineDatabaseConnection implements DatabaseConnectionInterface
{
    /**
     * @var Connection
     */
    private Connection $connection;

    /**
     * @param DatabaseConfig $config
     */
    public function __construct(DatabaseConfig $config)
    {
        $this->connection = DriverManager::getConnection($config->toConnectionParams());
    }

    /**
     * @inheritDoc
     */
    public function connection(): Connection
    {
        return $this->connection;
    }
}
