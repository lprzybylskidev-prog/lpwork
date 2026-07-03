<?php

declare(strict_types=1);

namespace LPWork\Middleware\Exceptions;

use RuntimeException;

/**
 * Reports middleware not found exception failures.
 */
final class MiddlewareNotFoundException extends RuntimeException
{
    /**
     * Creates a new MiddlewareNotFoundException instance.
     */
    public function __construct(string $middleware)
    {
        parent::__construct(sprintf('Middleware class does not exist: %s', $middleware));
    }
}
