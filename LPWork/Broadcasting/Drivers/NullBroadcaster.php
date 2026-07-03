<?php

declare(strict_types=1);

namespace LPWork\Broadcasting\Drivers;

use LPWork\Broadcasting\BroadcastMessage;
use LPWork\Broadcasting\BroadcastResult;
use LPWork\Broadcasting\Contracts\Broadcaster;

/**
 * Represents the null broadcaster framework component.
 */
final readonly class NullBroadcaster implements Broadcaster
{
    /**
     * Creates a new NullBroadcaster instance.
     */
    public function __construct(private string $name) {}

    /**
     * Runs broadcast.
     */
    public function broadcast(BroadcastMessage $message): BroadcastResult
    {
        return new BroadcastResult($this->name, $message->name(), $message->channels());
    }
}
