<?php

declare(strict_types=1);

namespace LPWork\Broadcasting;

/**
 * Represents the result of broadcast result work.
 */
final readonly class BroadcastResult
{
    /**
     * @param list<string> $channels
     */
    public function __construct(
        public string $driver,
        public string $event,
        public array $channels,
    ) {}
}
