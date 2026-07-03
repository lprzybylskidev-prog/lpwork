<?php

declare(strict_types=1);

namespace LPWork\Broadcasting\Events;

/**
 * Represents the broadcast sending framework component.
 */
final readonly class BroadcastSending
{
    /**
     * @param list<string> $channels
     */
    public function __construct(
        public string $event,
        public array $channels,
    ) {}
}
