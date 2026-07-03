<?php

declare(strict_types=1);

namespace LPWork\Session\Exceptions;

use RuntimeException;

/**
 * Reports missing session config exception failures.
 */
final class MissingSessionConfigException extends RuntimeException
{
    /**
     * Creates a new MissingSessionConfigException instance.
     */
    public function __construct(string $key)
    {
        parent::__construct("Missing session configuration value: {$key}.");
    }
}
