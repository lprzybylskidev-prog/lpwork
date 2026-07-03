<?php

declare(strict_types=1);

namespace LPWork\Schedule\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid schedule config exception failures.
 */
final class InvalidScheduleConfigException extends InvalidArgumentException
{
    /**
     * Creates a new InvalidScheduleConfigException instance.
     */
    public function __construct(string $key)
    {
        parent::__construct(sprintf('Invalid scheduler configuration value for [%s].', $key));
    }
}
