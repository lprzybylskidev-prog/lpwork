<?php

declare(strict_types=1);

namespace LPWork\Config\Exceptions;

use RuntimeException;

/**
 * Reports config cache write exception failures.
 */
final class ConfigCacheWriteException extends RuntimeException
{
    /**
     * Creates a new ConfigCacheWriteException instance.
     */
    public function __construct(string $path)
    {
        parent::__construct("Could not write config cache file: {$path}.");
    }
}
