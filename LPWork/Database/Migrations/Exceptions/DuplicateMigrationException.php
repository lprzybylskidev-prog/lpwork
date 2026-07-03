<?php

declare(strict_types=1);

namespace LPWork\Database\Migrations\Exceptions;

use InvalidArgumentException;

/**
 * Reports duplicate migration exception failures.
 */
final class DuplicateMigrationException extends InvalidArgumentException
{
    /**
     * Creates a new DuplicateMigrationException instance.
     */
    public function __construct(string $connection, string $migration)
    {
        parent::__construct(sprintf('Migration [%s] is already registered for connection [%s].', $migration, $connection));
    }
}
