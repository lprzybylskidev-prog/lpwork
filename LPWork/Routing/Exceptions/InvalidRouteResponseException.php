<?php

declare(strict_types=1);

namespace LPWork\Routing\Exceptions;

use RuntimeException;

/**
 * Reports invalid route response exception failures.
 */
final class InvalidRouteResponseException extends RuntimeException
{
    /**
     * Creates a new InvalidRouteResponseException instance.
     */
    public function __construct(string $controller, string $method)
    {
        if ($method === '') {
            parent::__construct($controller);

            return;
        }

        parent::__construct(sprintf(
            'Route action must return an HttpResponse: %s::%s',
            $controller,
            $method,
        ));
    }

    /**
     * Performs the for closure operation.
     */
    public static function forClosure(string $path): self
    {
        return new self('Route closure for [' . $path . '] must return an HttpResponse.', '');
    }
}
