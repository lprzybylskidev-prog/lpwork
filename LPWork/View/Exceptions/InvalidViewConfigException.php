<?php

declare(strict_types=1);

namespace LPWork\View\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid view config exception failures.
 */
final class InvalidViewConfigException extends InvalidArgumentException
{
    /**
     * Creates a new InvalidViewConfigException instance.
     */
    public function __construct(string $key)
    {
        parent::__construct(sprintf('View configuration value is invalid: %s.', $key));
    }
}
