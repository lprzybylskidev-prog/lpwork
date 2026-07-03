<?php

declare(strict_types=1);

namespace LPWork\Throttle\Events;

/**
 * Represents the cli command throttled framework component.
 */
final readonly class CliCommandThrottled
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public array $context,
    ) {}
}
