<?php

declare(strict_types=1);

namespace LPWork\Broadcasting\Events;

/**
 * Represents the broadcast sent framework component.
 */
final readonly class BroadcastSent
{
    /**
     * @param list<string> $channels
     */
    public function __construct(
        public string $event,
        public array $channels,
        public string $broadcaster,
    ) {}
}
