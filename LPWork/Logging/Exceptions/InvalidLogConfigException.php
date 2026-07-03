<?php

declare(strict_types=1);

namespace LPWork\Logging\Exceptions;

use RuntimeException;

/**
 * Reports invalid log config exception failures.
 */
final class InvalidLogConfigException extends RuntimeException
{
    /**
     * Creates a new InvalidLogConfigException instance.
     */
    public function __construct(string $key)
    {
        parent::__construct("Invalid log configuration value: {$key}.");
    }
}
