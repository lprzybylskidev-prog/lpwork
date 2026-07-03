<?php

declare(strict_types=1);

namespace LPWork\Throttle\Storage;

use LPWork\Throttle\Contracts\ThrottleStorage;
use LPWork\Throttle\ThrottleState;

/**
 * Represents the in memory throttle storage framework component.
 */
final class InMemoryThrottleStorage implements ThrottleStorage
{
    /**
     * @var array<string, array{attempts: int, expires_at: int}>
     */
    private array $entries = [];

    /**
     * Performs the hit operation.
     */
    public function hit(string $key, int $decaySeconds, int $now): ThrottleState
    {
        $entry = $this->entries[$key] ?? null;

        if ($entry === null || $entry['expires_at'] <= $now) {
            $entry = [
                'attempts' => 0,
                'expires_at' => $now + $decaySeconds,
            ];
        }

        $entry['attempts']++;
        $this->entries[$key] = $entry;

        return new ThrottleState(
            attempts: $entry['attempts'],
            retryAfter: max(1, $entry['expires_at'] - $now),
        );
    }
}
