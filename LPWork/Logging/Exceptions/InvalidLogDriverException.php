<?php

declare(strict_types=1);

namespace LPWork\Logging\Exceptions;

use RuntimeException;

/**
 * Reports invalid log driver exception failures.
 */
final class InvalidLogDriverException extends RuntimeException
{
    /**
     * Creates a new InvalidLogDriverException instance.
     */
    public function __construct(string $driver)
    {
        parent::__construct("Log driver is not supported: {$driver}.");
    }
}
