<?php

declare(strict_types=1);

namespace LPWork\Throttle\Storage;

use LPWork\Shared\Redis\RedisClient;
use LPWork\Throttle\Contracts\ThrottleStorage;
use LPWork\Throttle\ThrottleState;

/**
 * Represents the redis throttle storage framework component.
 */
final readonly class RedisThrottleStorage implements ThrottleStorage
{
    /**
     * Creates a new RedisThrottleStorage instance.
     */
    public function __construct(private RedisClient $redis) {}

    /**
     * Performs the hit operation.
     */
    public function hit(string $key, int $decaySeconds, int $now): ThrottleState
    {
        $key = 'throttle:' . $key;
        $attempts = $this->redis->incrementWindow($key, $decaySeconds);

        return new ThrottleState(
            attempts: $attempts,
            retryAfter: max(1, $this->redis->ttl($key)),
        );
    }
}
