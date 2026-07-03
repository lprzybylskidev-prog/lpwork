<?php

declare(strict_types=1);

namespace LPWork\Throttle\Exceptions;

use RuntimeException;

/**
 * Reports invalid throttle config exception failures.
 */
final class InvalidThrottleConfigException extends RuntimeException
{
    /**
     * Creates a new InvalidThrottleConfigException instance.
     */
    public function __construct(string $key)
    {
        parent::__construct(sprintf('Throttle configuration value is invalid: %s.', $key));
    }
}
