<?php

declare(strict_types=1);

namespace LPWork\Url\Exceptions;

use RuntimeException;

/**
 * Reports route name not found exception failures.
 */
final class RouteNameNotFoundException extends RuntimeException
{
    /**
     * Creates a new RouteNameNotFoundException instance.
     */
    public function __construct(string $name)
    {
        parent::__construct(sprintf('Route name does not exist: %s', $name));
    }
}
