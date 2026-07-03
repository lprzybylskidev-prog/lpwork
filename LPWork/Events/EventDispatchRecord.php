<?php

declare(strict_types=1);

namespace LPWork\Events;

/**
 * Represents the event dispatch record framework component.
 */
final class EventDispatchRecord
{
    /**
     * @param list<string> $listeners
     */
    public function __construct(
        public readonly string $event,
        public array $listeners = [],
        public ?float $durationMs = null,
        public bool $successful = true,
    ) {}

    /**
     * Registers or stores add listener.
     */
    public function addListener(string $listener): void
    {
        $this->listeners[] = $listener;
    }

    /**
     * Performs the finish operation.
     */
    public function finish(float $durationMs, bool $successful): void
    {
        $this->durationMs = $durationMs;
        $this->successful = $successful;
    }
}
