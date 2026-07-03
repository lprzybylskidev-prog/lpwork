<?php

declare(strict_types=1);

namespace LPWork\Routing\Exceptions;

use RuntimeException;

/**
 * Reports duplicate route name exception failures.
 */
final class DuplicateRouteNameException extends RuntimeException
{
    /**
     * Creates a new DuplicateRouteNameException instance.
     */
    public function __construct(string $name)
    {
        parent::__construct(sprintf('Route name already exists: %s', $name));
    }
}
