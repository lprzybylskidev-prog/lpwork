<?php

declare(strict_types=1);

namespace LPWork\Session\Exceptions;

use RuntimeException;

/**
 * Reports invalid session driver exception failures.
 */
final class InvalidSessionDriverException extends RuntimeException
{
    /**
     * Creates a new InvalidSessionDriverException instance.
     */
    public function __construct(string $driver)
    {
        parent::__construct("Session driver is not supported: {$driver}.");
    }
}
