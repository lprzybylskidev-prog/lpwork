<?php

declare(strict_types=1);

namespace LPWork\Database\Migrations\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid migration exception failures.
 */
final class InvalidMigrationException extends InvalidArgumentException
{
    /**
     * Creates a new InvalidMigrationException instance.
     */
    public function __construct(string $migration)
    {
        parent::__construct(sprintf('Migration [%s] must implement the migration contract.', $migration));
    }
}
