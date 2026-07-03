<?php

declare(strict_types=1);

namespace LPWork\Broadcasting\Contracts;

/**
 * Defines the contract for broadcastable event.
 */
interface BroadcastableEvent
{
    /**
     * @return list<string>
     */
    public function broadcastChannels(): array;

    /**
     * @return array<string, mixed>
     */
    public function broadcastPayload(): array;

    /**
     * Runs broadcast name.
     */
    public function broadcastName(): string;
}
