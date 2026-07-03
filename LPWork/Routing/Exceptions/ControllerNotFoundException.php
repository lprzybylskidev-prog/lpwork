<?php

declare(strict_types=1);

namespace LPWork\Routing\Exceptions;

use RuntimeException;

/**
 * Reports controller not found exception failures.
 */
final class ControllerNotFoundException extends RuntimeException
{
    /**
     * Creates a new ControllerNotFoundException instance.
     */
    public function __construct(string $controller)
    {
        parent::__construct(sprintf('Route controller does not exist: %s', $controller));
    }
}
