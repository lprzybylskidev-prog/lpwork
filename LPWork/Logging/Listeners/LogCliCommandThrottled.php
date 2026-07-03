<?php

declare(strict_types=1);

namespace LPWork\Logging\Listeners;

use LPWork\Logging\Contracts\Logger;
use LPWork\Throttle\Events\CliCommandThrottled;

/**
 * Represents the log cli command throttled framework component.
 */
final readonly class LogCliCommandThrottled
{
    /**
     * Creates a new LogCliCommandThrottled instance.
     */
    public function __construct(
        private Logger $logger,
    ) {}

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(CliCommandThrottled $event): void
    {
        $this->logger->warning('CLI command throttled.', $event->context);
    }
}
