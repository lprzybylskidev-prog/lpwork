<?php

declare(strict_types=1);

namespace LPWork\Url\Exceptions;

use RuntimeException;

/**
 * Reports missing route parameter exception failures.
 */
final class MissingRouteParameterException extends RuntimeException
{
    /**
     * Creates a new MissingRouteParameterException instance.
     */
    public function __construct(string $routeName, string $parameter)
    {
        parent::__construct(sprintf('Missing parameter [%s] for route [%s].', $parameter, $routeName));
    }
}
