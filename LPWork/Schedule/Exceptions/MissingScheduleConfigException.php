<?php

declare(strict_types=1);

namespace LPWork\Schedule\Exceptions;

use InvalidArgumentException;

/**
 * Reports missing schedule config exception failures.
 */
final class MissingScheduleConfigException extends InvalidArgumentException
{
    /**
     * Creates a new MissingScheduleConfigException instance.
     */
    public function __construct(string $key)
    {
        parent::__construct(sprintf('Missing scheduler configuration value for [%s].', $key));
    }
}
