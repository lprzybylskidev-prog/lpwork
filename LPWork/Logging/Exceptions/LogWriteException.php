<?php

declare(strict_types=1);

namespace LPWork\Logging\Exceptions;

use RuntimeException;

/**
 * Reports log write exception failures.
 */
final class LogWriteException extends RuntimeException
{
    /**
     * Creates a new LogWriteException instance.
     */
    public function __construct(string $path)
    {
        parent::__construct("Could not write log file: {$path}.");
    }
}
