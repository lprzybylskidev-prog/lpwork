<?php

declare(strict_types=1);

namespace LPWork\Cache\Exceptions;

use RuntimeException;

/**
 * Reports cache write exception failures.
 */
final class CacheWriteException extends RuntimeException
{
    /**
     * Creates a new CacheWriteException instance.
     */
    public function __construct(string $path)
    {
        parent::__construct("Could not write cache file: {$path}.");
    }
}
