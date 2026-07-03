<?php

declare(strict_types=1);

namespace LPWork\Cache\Exceptions;

use RuntimeException;

/**
 * Reports missing cache config exception failures.
 */
final class MissingCacheConfigException extends RuntimeException
{
    /**
     * Creates a new MissingCacheConfigException instance.
     */
    public function __construct(string $key)
    {
        parent::__construct("Missing cache configuration value: {$key}.");
    }
}
