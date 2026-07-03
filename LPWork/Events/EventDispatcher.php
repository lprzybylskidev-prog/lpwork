<?php

declare(strict_types=1);

namespace LPWork\Events;

/**
 * Represents the event dispatcher framework component.
 */
final readonly class EventDispatcher
{
    /**
     * Creates a new EventDispatcher instance.
     */
    public function __construct(
        private EventRegistry $registry,
        private ListenerResolver $resolver,
        private EventDebugCollector $collector,
    ) {}

    /**
     * Runs dispatch.
     */
    public function dispatch(object $event): object
    {
        $started = hrtime(true);
        $record = $this->collector->start($event);
        $successful = false;

        try {
            foreach ($this->registry->listenersFor($event) as $listener) {
                $record->addListener($this->resolver->name($listener));
                ($this->resolver->resolve($listener))($event);
            }

            $successful = true;

            return $event;
        } finally {
            $this->collector->finish(
                record: $record,
                durationMs: $this->durationMs($started),
                successful: $successful,
                recordedAtMs: $this->epochMsForHrtime($started),
            );
        }
    }

    private function durationMs(int|float $started): float
    {
        return round(max(0.0, hrtime(true) - $started) / 1_000_000, 3);
    }

    private function epochMsForHrtime(int|float $timestamp): float
    {
        $now = hrtime(true);

        return round((microtime(true) * 1000) - (($now - $timestamp) / 1_000_000), 3);
    }
}
