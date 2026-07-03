<?php

declare(strict_types=1);

namespace LPWork\Database\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Reports database connection exception failures.
 */
final class DatabaseConnectionException extends RuntimeException
{
    /**
     * Performs the failed operation.
     */
    public static function failed(string $connection, Throwable $previous): self
    {
        return new self(sprintf('Could not connect to database connection [%s].', $connection), previous: $previous);
    }
}
