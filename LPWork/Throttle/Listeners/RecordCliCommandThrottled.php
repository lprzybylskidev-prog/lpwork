<?php

declare(strict_types=1);

namespace LPWork\Throttle\Listeners;

use LPWork\Throttle\Events\CliCommandThrottled;
use LPWork\Throttle\ThrottleDebugCollector;

/**
 * Represents the record cli command throttled framework component.
 */
final readonly class RecordCliCommandThrottled
{
    /**
     * Creates a new RecordCliCommandThrottled instance.
     */
    public function __construct(
        private ThrottleDebugCollector $collector,
    ) {}

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(CliCommandThrottled $event): void
    {
        $this->collector->throttled('cli', $event->context);
    }
}
