<?php

declare(strict_types=1);

namespace LPWork\Database\Exceptions;

use InvalidArgumentException;

/**
 * Reports missing database config exception failures.
 */
final class MissingDatabaseConfigException extends InvalidArgumentException
{
    /**
     * Creates a new MissingDatabaseConfigException instance.
     */
    public function __construct(string $key)
    {
        parent::__construct(sprintf('Missing database configuration value for [%s].', $key));
    }
}
