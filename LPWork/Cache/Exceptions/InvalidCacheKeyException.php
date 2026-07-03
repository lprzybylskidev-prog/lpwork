<?php

declare(strict_types=1);

namespace LPWork\Cache\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid cache key exception failures.
 */
final class InvalidCacheKeyException extends InvalidArgumentException
{
    /**
     * Creates a new InvalidCacheKeyException instance.
     */
    public function __construct()
    {
        parent::__construct('Cache key must be a non-empty string.');
    }
}
