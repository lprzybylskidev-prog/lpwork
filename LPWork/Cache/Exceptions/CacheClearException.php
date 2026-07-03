<?php

declare(strict_types=1);

namespace LPWork\Cache\Exceptions;

use RuntimeException;

/**
 * Reports cache clear exception failures.
 */
final class CacheClearException extends RuntimeException
{
    /**
     * Creates a new CacheClearException instance.
     */
    public function __construct(string $path)
    {
        parent::__construct("Could not clear cache path: {$path}.");
    }
}
