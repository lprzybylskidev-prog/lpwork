<?php

declare(strict_types=1);

namespace LPWork\Throttle\Events;

/**
 * Represents the http request throttled framework component.
 */
final readonly class HttpRequestThrottled
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public array $context,
    ) {}
}
