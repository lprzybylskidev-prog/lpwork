<?php

declare(strict_types=1);

namespace Tests\support\testing\Throttle;

use LPWork\Throttle\Contracts\ThrottleClock;
use PHPUnit\Framework\Assert;

final class TestThrottleClock implements ThrottleClock
{
    public function __construct(
        private int $now = 1000,
    ) {}

    public function now(): int
    {
        return $this->now;
    }

    public function travel(int $seconds): self
    {
        $this->now += $seconds;

        return $this;
    }

    public function assertNow(int $now): self
    {
        Assert::assertSame($now, $this->now, 'Unexpected throttle clock time.');

        return $this;
    }
}
