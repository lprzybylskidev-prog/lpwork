<?php

declare(strict_types=1);

namespace LPWork\Config\Exceptions;

use RuntimeException;

/**
 * Reports invalid value exception failures.
 */
final class InvalidValueException extends RuntimeException
{
    /**
     * Creates a new InvalidValueException instance.
     */
    public function __construct(string $key, string $expectedType)
    {
        parent::__construct("Invalid config value for {$key}. Expected type: {$expectedType}.");
    }
}
