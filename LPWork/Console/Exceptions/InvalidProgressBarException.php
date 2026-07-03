<?php

declare(strict_types=1);

namespace LPWork\Console\Exceptions;

use RuntimeException;

/**
 * Reports invalid progress bar exception failures.
 */
final class InvalidProgressBarException extends RuntimeException
{
    /**
     * Performs the negative total operation.
     */
    public static function negativeTotal(): self
    {
        return new self('Progress bar total cannot be negative.');
    }

    /**
     * Performs the invalid width operation.
     */
    public static function invalidWidth(): self
    {
        return new self('Progress bar width must be greater than zero.');
    }
}
