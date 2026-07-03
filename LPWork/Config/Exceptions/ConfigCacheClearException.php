<?php

declare(strict_types=1);

namespace LPWork\Config\Exceptions;

use RuntimeException;

/**
 * Reports config cache clear exception failures.
 */
final class ConfigCacheClearException extends RuntimeException
{
    /**
     * Creates a new ConfigCacheClearException instance.
     */
    public function __construct(string $path)
    {
        parent::__construct("Could not clear config cache file: {$path}.");
    }
}
