<?php

declare(strict_types=1);

namespace LPWork\Database\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid database config exception failures.
 */
final class InvalidDatabaseConfigException extends InvalidArgumentException
{
    /**
     * Creates a new InvalidDatabaseConfigException instance.
     */
    public function __construct(string $key)
    {
        parent::__construct(sprintf('Invalid database configuration value for [%s].', $key));
    }
}
