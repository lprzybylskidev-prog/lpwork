<?php

declare(strict_types=1);

namespace LPWork\Database\Seeders\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid seeder exception failures.
 */
final class InvalidSeederException extends InvalidArgumentException
{
    /**
     * Creates a new InvalidSeederException instance.
     */
    public function __construct(string $seeder)
    {
        parent::__construct(sprintf('Seeder [%s] must implement the seeder contract.', $seeder));
    }
}
