<?php

declare(strict_types=1);

namespace LPWork\Routing\Exceptions;

use RuntimeException;

/**
 * Reports invalid route action exception failures.
 */
final class InvalidRouteActionException extends RuntimeException
{
    /**
     * Creates a new InvalidRouteActionException instance.
     */
    public function __construct()
    {
        parent::__construct('Route action must contain controller class and method name.');
    }
}
