<?php

declare(strict_types=1);

namespace Tests\support\cache;

use DateTimeImmutable;
use DateTimeZone;
use LPWork\Time\Contracts\Clock;

final class MutableCacheClock implements Clock
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
