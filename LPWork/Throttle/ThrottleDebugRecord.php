<?php

declare(strict_types=1);

namespace LPWork\Throttle;

/**
 * Represents the throttle debug record framework component.
 */
final readonly class ThrottleDebugRecord
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public string $flow,
        public array $context,
    ) {}
}
