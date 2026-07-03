<?php

declare(strict_types=1);

namespace LPWork\Cache\Exceptions;

use RuntimeException;

/**
 * Reports invalid cache store exception failures.
 */
final class InvalidCacheStoreException extends RuntimeException
{
    /**
     * Creates a new InvalidCacheStoreException instance.
     */
    public function __construct(string $store)
    {
        parent::__construct("Cache store is not configured: {$store}.");
    }
}
