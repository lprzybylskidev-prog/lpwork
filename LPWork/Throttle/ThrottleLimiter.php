<?php

declare(strict_types=1);

namespace LPWork\Throttle;

use LPWork\Throttle\Contracts\ThrottleClock;
use LPWork\Throttle\Contracts\ThrottleStorage;

/**
 * Represents the throttle limiter framework component.
 */
final readonly class ThrottleLimiter
{
    /**
     * Creates a new ThrottleLimiter instance.
     */
    public function __construct(
        private ThrottleStorage $storage,
        private ThrottleClock $clock,
    ) {}

    /**
     * Performs the attempt operation.
     */
    public function attempt(ThrottlePolicy $policy, string $key): ThrottleResult
    {
        if (!$policy->enabled()) {
            return ThrottleResult::allowedWithoutLimit();
        }

        $state = $this->storage->hit(
            key: $policy->name() . ':' . $key,
            decaySeconds: $policy->decaySeconds(),
            now: $this->clock->now(),
        );

        return new ThrottleResult(
            allowed: $state->attempts() <= $policy->maxAttempts(),
            attempts: $state->attempts(),
            maxAttempts: $policy->maxAttempts(),
            retryAfter: $state->retryAfter(),
        );
    }
}
