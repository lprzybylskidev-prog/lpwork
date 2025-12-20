<?php
declare(strict_types=1);

namespace LPwork\Database\Contract;

use Doctrine\DBAL\Connection;

/**
 * Represents a database connection abstraction.
 */
interface DatabaseConnectionInterface
{
    /**
     * Returns the underlying Doctrine DBAL connection.
     *
     * @return Connection
     */
    public function connection(): Connection;
}
