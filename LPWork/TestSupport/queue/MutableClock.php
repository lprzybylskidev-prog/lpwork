<?php

declare(strict_types=1);

namespace Tests\support\queue;

use DateTimeImmutable;
use DateTimeZone;
use LPWork\Time\Contracts\Clock;

final class MutableClock implements Clock
{
    public function __construct(
        private int $timestamp = 1000,
    ) {}

    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('@' . $this->timestamp, new DateTimeZone('UTC'));
    }

    public function travel(int $seconds): void
    {
        $this->timestamp += $seconds;
    }
}
