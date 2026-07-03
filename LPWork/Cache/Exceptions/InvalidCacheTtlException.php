<?php

declare(strict_types=1);

namespace LPWork\Cache\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid cache ttl exception failures.
 */
final class InvalidCacheTtlException extends InvalidArgumentException
{
    /**
     * Creates a new InvalidCacheTtlException instance.
     */
    public function __construct()
    {
        parent::__construct('Cache TTL must be greater than zero seconds.');
    }
}
