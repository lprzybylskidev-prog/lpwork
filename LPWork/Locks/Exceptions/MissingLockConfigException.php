<?php

declare(strict_types=1);

namespace LPWork\Locks\Exceptions;

use InvalidArgumentException;

/**
 * Reports missing lock config exception failures.
 */
final class MissingLockConfigException extends InvalidArgumentException
{
    /**
     * Creates a new MissingLockConfigException instance.
     */
    public function __construct(string $key)
    {
        parent::__construct(sprintf('Lock configuration value is missing: %s.', $key));
    }
}
