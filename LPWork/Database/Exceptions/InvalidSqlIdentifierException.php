<?php

declare(strict_types=1);

namespace LPWork\Database\Exceptions;

use RuntimeException;

/**
 * Reports invalid sql identifier exception failures.
 */
final class InvalidSqlIdentifierException extends RuntimeException
{
    /**
     * Creates a new InvalidSqlIdentifierException instance.
     */
    public function __construct(string $identifier)
    {
        parent::__construct("SQL identifier is invalid: {$identifier}.");
    }
}
