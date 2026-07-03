<?php

declare(strict_types=1);

namespace LPWork\Throttle\Contracts;

/**
 * Defines the contract for throttle clock.
 */
interface ThrottleClock
{
    /**
     * Performs the now operation.
     */
    public function now(): int;
}
