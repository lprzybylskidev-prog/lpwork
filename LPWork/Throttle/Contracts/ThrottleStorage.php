<?php

declare(strict_types=1);

namespace LPWork\Throttle\Contracts;

use LPWork\Throttle\ThrottleState;

/**
 * Defines the contract for throttle storage.
 */
interface ThrottleStorage
{
    /**
     * Performs the hit operation.
     */
    public function hit(string $key, int $decaySeconds, int $now): ThrottleState;
}
