<?php

declare(strict_types=1);

namespace LPWork\Foundation\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid runtime environment exception failures.
 */
final class InvalidRuntimeEnvironmentException extends InvalidArgumentException
{
    /**
     * Performs the empty name operation.
     */
    public static function emptyName(): self
    {
        return new self('Runtime environment name must not be empty.');
    }

    /**
     * Performs the invalid production environment operation.
     */
    public static function invalidProductionEnvironment(): self
    {
        return new self('Production environment names must be non-empty strings.');
    }
}
