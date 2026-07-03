<?php

declare(strict_types=1);

namespace LPWork\Cache\Exceptions;

use RuntimeException;

/**
 * Reports invalid cache driver exception failures.
 */
final class InvalidCacheDriverException extends RuntimeException
{
    /**
     * Creates a new InvalidCacheDriverException instance.
     */
    public function __construct(string $driver)
    {
        parent::__construct("Cache driver is not supported: {$driver}.");
    }
}
