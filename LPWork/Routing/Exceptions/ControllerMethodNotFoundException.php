<?php

declare(strict_types=1);

namespace LPWork\Routing\Exceptions;

use RuntimeException;

/**
 * Reports controller method not found exception failures.
 */
final class ControllerMethodNotFoundException extends RuntimeException
{
    /**
     * Creates a new ControllerMethodNotFoundException instance.
     */
    public function __construct(string $controller, string $method)
    {
        parent::__construct(sprintf('Route controller method does not exist: %s::%s', $controller, $method));
    }
}
