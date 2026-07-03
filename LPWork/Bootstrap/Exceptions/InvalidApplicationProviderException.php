<?php

declare(strict_types=1);

namespace LPWork\Bootstrap\Exceptions;

use RuntimeException;

use function sprintf;

/**
 * Reports invalid application provider exception failures.
 */
final class InvalidApplicationProviderException extends RuntimeException
{
    /**
     * Reports whether missing.
     */
    public static function missing(string $class): self
    {
        return new self(sprintf('Application provider [%s] does not exist.', $class));
    }

    /**
     * Performs the invalid operation.
     */
    public static function invalid(string $class, string $expected): self
    {
        return new self(sprintf('Application provider [%s] must extend or implement [%s].', $class, $expected));
    }
}
