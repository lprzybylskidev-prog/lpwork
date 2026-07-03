<?php

declare(strict_types=1);

namespace LPWork\Throttle\Exceptions;

use RuntimeException;

/**
 * Reports throttle policy not found exception failures.
 */
final class ThrottlePolicyNotFoundException extends RuntimeException
{
    /**
     * Creates a new ThrottlePolicyNotFoundException instance.
     */
    public function __construct(string $policy)
    {
        parent::__construct(sprintf('Throttle policy is not configured: %s.', $policy));
    }
}
