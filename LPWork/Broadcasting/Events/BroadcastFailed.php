<?php

declare(strict_types=1);

namespace LPWork\Broadcasting\Events;

/**
 * Represents the broadcast failed framework component.
 */
final readonly class BroadcastFailed
{
    /**
     * @param list<string> $channels
     */
    public function __construct(
        public string $event,
        public array $channels,
        public string $exception,
    ) {}
}
