<?php

declare(strict_types=1);

namespace LPWork\Cache\Exceptions;

use RuntimeException;

/**
 * Reports cache read exception failures.
 */
final class CacheReadException extends RuntimeException
{
    /**
     * Creates a new CacheReadException instance.
     */
    public function __construct(string $path)
    {
        parent::__construct("Could not read cache file: {$path}.");
    }
}
