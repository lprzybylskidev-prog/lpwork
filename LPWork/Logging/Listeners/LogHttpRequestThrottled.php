<?php

declare(strict_types=1);

namespace LPWork\Logging\Listeners;

use LPWork\Logging\Contracts\Logger;
use LPWork\Throttle\Events\HttpRequestThrottled;

/**
 * Represents the log http request throttled framework component.
 */
final readonly class LogHttpRequestThrottled
{
    /**
     * Creates a new LogHttpRequestThrottled instance.
     */
    public function __construct(
        private Logger $logger,
    ) {}

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(HttpRequestThrottled $event): void
    {
        $this->logger->warning('HTTP request throttled.', $event->context);
    }
}
