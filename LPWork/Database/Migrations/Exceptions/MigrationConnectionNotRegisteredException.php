<?php

declare(strict_types=1);

namespace LPWork\Database\Migrations\Exceptions;

use InvalidArgumentException;

/**
 * Reports migration connection not registered exception failures.
 */
final class MigrationConnectionNotRegisteredException extends InvalidArgumentException
{
    /**
     * Creates a new MigrationConnectionNotRegisteredException instance.
     */
    public function __construct(string $connection)
    {
        parent::__construct(sprintf('No migrations are registered for connection [%s].', $connection));
    }
}
