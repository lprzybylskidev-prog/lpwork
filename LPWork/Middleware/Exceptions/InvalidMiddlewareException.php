<?php

declare(strict_types=1);

namespace LPWork\Middleware\Exceptions;

use LPWork\Middleware\Contracts\Middleware;
use RuntimeException;

/**
 * Reports invalid middleware exception failures.
 */
final class InvalidMiddlewareException extends RuntimeException
{
    /**
     * Creates a new InvalidMiddlewareException instance.
     */
    public function __construct(string $middleware)
    {
        parent::__construct(sprintf(
            'Middleware must implement %s: %s',
            Middleware::class,
            $middleware,
        ));
    }
}
