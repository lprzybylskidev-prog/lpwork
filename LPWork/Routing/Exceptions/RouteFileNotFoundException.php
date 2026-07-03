<?php

declare(strict_types=1);

namespace LPWork\Routing\Exceptions;

use RuntimeException;

/**
 * Reports route file not found exception failures.
 */
final class RouteFileNotFoundException extends RuntimeException
{
    /**
     * Creates a new RouteFileNotFoundException instance.
     */
    public function __construct(string $path)
    {
        parent::__construct(sprintf('Route file does not exist: %s', $path));
    }
}
