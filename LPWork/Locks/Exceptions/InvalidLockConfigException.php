<?php

declare(strict_types=1);

namespace LPWork\Locks\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid lock config exception failures.
 */
final class InvalidLockConfigException extends InvalidArgumentException
{
    /**
     * Creates a new InvalidLockConfigException instance.
     */
    public function __construct(string $key)
    {
        parent::__construct(sprintf('Lock configuration value is invalid: %s.', $key));
    }
}
