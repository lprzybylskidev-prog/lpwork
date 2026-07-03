<?php

declare(strict_types=1);

namespace LPWork\Database\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid database connection exception failures.
 */
final class InvalidDatabaseConnectionException extends InvalidArgumentException
{
    /**
     * Creates a new InvalidDatabaseConnectionException instance.
     */
    public function __construct(string $name)
    {
        parent::__construct(sprintf('Database connection [%s] is not configured.', $name));
    }
}
