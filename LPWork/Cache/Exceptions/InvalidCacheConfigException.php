<?php

declare(strict_types=1);

namespace LPWork\Cache\Exceptions;

use RuntimeException;

/**
 * Reports invalid cache config exception failures.
 */
final class InvalidCacheConfigException extends RuntimeException
{
    /**
     * Creates a new InvalidCacheConfigException instance.
     */
    public function __construct(string $key)
    {
        parent::__construct("Invalid cache configuration value: {$key}.");
    }
}
