<?php

declare(strict_types=1);

namespace LPWork\Throttle;

use LPWork\Throttle\Contracts\ThrottleClock;

/**
 * Represents the system throttle clock framework component.
 */
final readonly class SystemThrottleClock implements ThrottleClock
{
    /**
     * Performs the now operation.
     */
    public function now(): int
    {
        return time();
    }
}
