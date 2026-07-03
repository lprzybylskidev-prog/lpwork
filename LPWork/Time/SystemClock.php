<?php

declare(strict_types=1);

namespace LPWork\Time;

use DateTimeImmutable;
use DateTimeZone;
use LPWork\Time\Contracts\Clock;

/**
 * Represents the system clock framework component.
 */
final readonly class SystemClock implements Clock
{
    /**
     * Creates a new SystemClock instance.
     */
    public function __construct(
        private DateTimeZone $timezone = new DateTimeZone('UTC'),
    ) {}

    /**
     * Performs the now operation.
     */
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', $this->timezone);
    }
}
