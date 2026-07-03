<?php

declare(strict_types=1);

namespace LPWork\Logging\Exceptions;

use RuntimeException;

/**
 * Reports missing log config exception failures.
 */
final class MissingLogConfigException extends RuntimeException
{
    /**
     * Creates a new MissingLogConfigException instance.
     */
    public function __construct(string $key)
    {
        parent::__construct("Missing log configuration value: {$key}.");
    }
}
