<?php

declare(strict_types=1);

namespace LPWork\Database\Exceptions;

use InvalidArgumentException;

/**
 * Reports unsupported database driver exception failures.
 */
final class UnsupportedDatabaseDriverException extends InvalidArgumentException
{
    /**
     * Creates a new UnsupportedDatabaseDriverException instance.
     */
    public function __construct(string $driver)
    {
        parent::__construct(sprintf('Database driver [%s] is not supported.', $driver));
    }
}
