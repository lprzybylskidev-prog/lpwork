<?php

declare(strict_types=1);

namespace LPWork\Database\Seeders\Exceptions;

use InvalidArgumentException;

/**
 * Reports seeder connection not registered exception failures.
 */
final class SeederConnectionNotRegisteredException extends InvalidArgumentException
{
    /**
     * Creates a new SeederConnectionNotRegisteredException instance.
     */
    public function __construct(string $connection)
    {
        parent::__construct(sprintf('No seeders are registered for connection [%s].', $connection));
    }
}
