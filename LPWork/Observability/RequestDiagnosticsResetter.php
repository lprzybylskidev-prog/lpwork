<?php

declare(strict_types=1);

namespace LPWork\Observability;

use LPWork\Cache\CacheDebugCollector;
use LPWork\Database\DatabaseDebugCollector;
use LPWork\Events\EventDebugCollector;
use LPWork\Queue\QueueDebugCollector;
use LPWork\Schedule\ScheduleDebugCollector;
use LPWork\Security\SecurityDebugCollector;
use LPWork\Throttle\ThrottleDebugCollector;
use LPWork\View\ViewDebugCollector;

/**
 * Represents the request diagnostics resetter framework component.
 */
final readonly class RequestDiagnosticsResetter
{
    /**
     * Creates a new RequestDiagnosticsResetter instance.
     */
    public function __construct(
        private DiagnosticsCollector $diagnostics,
        private MetricCollector $metrics,
        private ?CacheDebugCollector $cache = null,
        private ?DatabaseDebugCollector $database = null,
        private ?EventDebugCollector $events = null,
        private ?QueueDebugCollector $queue = null,
        private ?ScheduleDebugCollector $schedule = null,
        private ?SecurityDebugCollector $security = null,
        private ?ThrottleDebugCollector $throttle = null,
        private ?ViewDebugCollector $views = null,
    ) {}

    /**
     * Resets this component to an empty lifecycle state.
     */
    public function reset(): void
    {
        $this->diagnostics->reset();
        $this->metrics->reset();
        $this->cache?->reset();
        $this->database?->reset();
        $this->events?->reset();
        $this->queue?->reset();
        $this->schedule?->reset();
        $this->security?->reset();
        $this->throttle?->reset();
        $this->views?->reset();
    }
}
