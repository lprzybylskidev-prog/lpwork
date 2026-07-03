<?php

declare(strict_types=1);

namespace LPWork\Database\Seeders\Exceptions;

use InvalidArgumentException;

/**
 * Reports duplicate seeder exception failures.
 */
final class DuplicateSeederException extends InvalidArgumentException
{
    /**
     * Creates a new DuplicateSeederException instance.
     */
    public function __construct(string $connection, string $seeder)
    {
        parent::__construct(sprintf('Seeder [%s] is already registered for connection [%s].', $seeder, $connection));
    }
}
