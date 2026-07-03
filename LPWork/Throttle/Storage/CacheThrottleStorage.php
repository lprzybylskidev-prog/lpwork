<?php

declare(strict_types=1);

namespace LPWork\Throttle\Storage;

use LPWork\Cache\CacheStore;
use LPWork\Throttle\Contracts\ThrottleStorage;
use LPWork\Throttle\ThrottleState;

/**
 * Represents the cache throttle storage framework component.
 */
final readonly class CacheThrottleStorage implements ThrottleStorage
{
    /**
     * Creates a new CacheThrottleStorage instance.
     */
    public function __construct(private CacheStore $cache) {}

    /**
     * Performs the hit operation.
     */
    public function hit(string $key, int $decaySeconds, int $now): ThrottleState
    {
        $cacheKey = 'throttle:' . $key;
        $entry = $this->cache->get($cacheKey);

        if (!$this->validEntry($entry) || $entry['expires_at'] <= $now) {
            $entry = [
                'attempts' => 0,
                'expires_at' => $now + $decaySeconds,
            ];
        }

        $entry['attempts']++;
        $this->cache->put($cacheKey, $entry, max(1, $entry['expires_at'] - $now));

        return new ThrottleState(
            attempts: $entry['attempts'],
            retryAfter: max(1, $entry['expires_at'] - $now),
        );
    }

    /**
     * @phpstan-assert-if-true array{attempts: int, expires_at: int} $entry
     */
    private function validEntry(mixed $entry): bool
    {
        return is_array($entry)
            && isset($entry['attempts'], $entry['expires_at'])
            && is_int($entry['attempts'])
            && is_int($entry['expires_at']);
    }
}
