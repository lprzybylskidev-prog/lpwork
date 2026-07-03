<?php

declare(strict_types=1);

namespace LPWork\Kernels\Cli\Exceptions;

use RuntimeException;

use function sprintf;

/**
 * Reports invalid cli application path exception failures.
 */
final class InvalidCliApplicationPathException extends RuntimeException
{
    /**
     * Performs the invalid operation.
     */
    public static function invalid(string $source, string $path): self
    {
        return new self(sprintf('Cannot resolve CLI application path from %s [%s]: directory does not exist.', $source, $path));
    }

    /**
     * Performs the not found operation.
     */
    public static function notFound(string $path): self
    {
        return new self(sprintf('Cannot locate an LPWork application root from [%s]. Run lpwork from the application root or set LPWORK_BASE_PATH.', $path));
    }
}
