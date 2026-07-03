<?php

declare(strict_types=1);

namespace LPWork\Time\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid timezone exception failures.
 */
final class InvalidTimezoneException extends InvalidArgumentException
{
    /**
     * Creates a new InvalidTimezoneException instance.
     */
    public function __construct(string $timezone)
    {
        parent::__construct(sprintf('Application timezone is invalid: %s.', $timezone));
    }
}
