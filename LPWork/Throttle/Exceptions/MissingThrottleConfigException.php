<?php

declare(strict_types=1);

namespace LPWork\Throttle\Exceptions;

use RuntimeException;

/**
 * Reports missing throttle config exception failures.
 */
final class MissingThrottleConfigException extends RuntimeException
{
    /**
     * Creates a new MissingThrottleConfigException instance.
     */
    public function __construct(string $key)
    {
        parent::__construct(sprintf('Throttle configuration value is missing: %s.', $key));
    }
}
