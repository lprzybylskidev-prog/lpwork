<?php

declare(strict_types=1);

namespace Tests\support\throttle;

use LPWork\Throttle\Contracts\ThrottleClock;

final class MutableThrottleClock implements ThrottleClock
{
    public function __construct(private int $now = 1000) {}

    public function now(): int
    {
        return $this->now;
    }

    public function advance(int $seconds): void
    {
        $this->now += $seconds;
    }
}
