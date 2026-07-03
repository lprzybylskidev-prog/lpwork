<?php

declare(strict_types=1);

namespace Tests\support\testing\Throttle;

use LPWork\Throttle\Contracts\ThrottleStorage;
use LPWork\Throttle\ThrottleState;
use PHPUnit\Framework\Assert;

final class TestThrottleStorage implements ThrottleStorage
{
    /**
     * @var array<string, array{attempts: int, expires_at: int}>
     */
    private array $entries = [];

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

    public function assertAttempts(string $key, int $attempts): self
    {
        Assert::assertSame($attempts, $this->entries[$key]['attempts'] ?? 0, sprintf('Unexpected throttle attempts for [%s].', $key));

        return $this;
    }

    public function assertMissing(string $key): self
    {
        Assert::assertArrayNotHasKey($key, $this->entries, sprintf('Throttle key [%s] exists unexpectedly.', $key));

        return $this;
    }
}
