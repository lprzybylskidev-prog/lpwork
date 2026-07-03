<?php

declare(strict_types=1);

namespace LPWork\Session\Exceptions;

use RuntimeException;

/**
 * Reports invalid session config exception failures.
 */
final class InvalidSessionConfigException extends RuntimeException
{
    /**
     * Creates a new InvalidSessionConfigException instance.
     */
    public function __construct(string $key)
    {
        parent::__construct("Invalid session configuration value: {$key}.");
    }
}
