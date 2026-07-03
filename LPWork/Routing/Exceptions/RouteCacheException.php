<?php

declare(strict_types=1);

namespace LPWork\Routing\Exceptions;

use RuntimeException;

/**
 * Reports route cache exception failures.
 */
final class RouteCacheException extends RuntimeException
{
    /**
     * Performs the closure route operation.
     */
    public static function closureRoute(string $path): self
    {
        return new self(sprintf('Cannot cache closure route [%s]. Use a controller action before running route:cache.', $path));
    }

    /**
     * Performs the invalid operation.
     */
    public static function invalid(string $path): self
    {
        return new self(sprintf('Route cache file is invalid: %s.', $path));
    }
}
