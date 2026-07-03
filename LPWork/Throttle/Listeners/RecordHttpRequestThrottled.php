<?php

declare(strict_types=1);

namespace LPWork\Throttle\Listeners;

use LPWork\Throttle\Events\HttpRequestThrottled;
use LPWork\Throttle\ThrottleDebugCollector;

/**
 * Represents the record http request throttled framework component.
 */
final readonly class RecordHttpRequestThrottled
{
    /**
     * Creates a new RecordHttpRequestThrottled instance.
     */
    public function __construct(
        private ThrottleDebugCollector $collector,
    ) {}

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(HttpRequestThrottled $event): void
    {
        $flow = $event->context['flow'] ?? 'http';

        $this->collector->throttled(is_string($flow) ? $flow : 'http', $event->context);
    }
}
