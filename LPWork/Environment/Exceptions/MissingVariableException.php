<?php

declare(strict_types=1);

namespace LPWork\Environment\Exceptions;

use RuntimeException;

/**
 * Reports missing variable exception failures.
 */
final class MissingVariableException extends RuntimeException
{
    /**
     * Creates a new MissingVariableException instance.
     */
    public function __construct(string $key)
    {
        parent::__construct("Missing environment variable: {$key}.");
    }
}
