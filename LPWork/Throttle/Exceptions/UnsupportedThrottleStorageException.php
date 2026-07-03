<?php

declare(strict_types=1);

namespace LPWork\Throttle\Exceptions;

use RuntimeException;

/**
 * Reports unsupported throttle storage exception failures.
 */
final class UnsupportedThrottleStorageException extends RuntimeException
{
    /**
     * Creates a new UnsupportedThrottleStorageException instance.
     */
    public function __construct(string $storage)
    {
        parent::__construct(sprintf('Throttle storage is not supported: %s.', $storage));
    }
}
