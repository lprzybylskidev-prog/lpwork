<?php

declare(strict_types=1);

namespace LPWork\Routing\Exceptions;

use RuntimeException;

/**
 * Reports closure route parameter exception failures.
 */
final class ClosureRouteParameterException extends RuntimeException
{
    /**
     * Reports whether cannot resolve.
     */
    public static function cannotResolve(string $path, string $parameter): self
    {
        return new self(sprintf(
            'Route closure for [%s] cannot resolve parameter [$%s].',
            $path,
            $parameter,
        ));
    }
}
