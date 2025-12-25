<?php
declare(strict_types=1);

namespace LPwork\Database\Contract;

/**
 * Contract for accessing named database connections.
 */
interface DatabaseConnectionManagerInterface
{
    /**
     * Returns a connection by name or the default one.
     *
     * @param string|null $name
     *
     * @return DatabaseConnectionInterface
     */
    public function get(?string $name = null): DatabaseConnectionInterface;

    /**
     * Returns configured connection names.
     *
     * @return array<int, string>
     */
    public function getConnectionNames(): array;

    /**
     * Returns the default connection name.
     *
     * @return string
     */
    public function getDefaultConnectionName(): string;
}
