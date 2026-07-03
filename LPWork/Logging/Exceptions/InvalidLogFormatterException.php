<?php

declare(strict_types=1);

namespace LPWork\Logging\Exceptions;

use RuntimeException;

/**
 * Reports invalid log formatter exception failures.
 */
final class InvalidLogFormatterException extends RuntimeException
{
    /**
     * Creates a new InvalidLogFormatterException instance.
     */
    public function __construct(string $formatter)
    {
        parent::__construct("Log formatter is not supported: {$formatter}.");
    }
}
