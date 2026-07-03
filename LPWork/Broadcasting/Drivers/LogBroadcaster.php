<?php

declare(strict_types=1);

namespace LPWork\Broadcasting\Drivers;

use LPWork\Broadcasting\BroadcastMessage;
use LPWork\Broadcasting\BroadcastResult;
use LPWork\Broadcasting\Contracts\Broadcaster;
use LPWork\Logging\Contracts\Logger;
use LPWork\Logging\Enums\LogLevel;

/**
 * Represents the log broadcaster framework component.
 */
final readonly class LogBroadcaster implements Broadcaster
{
    /**
     * Creates a new LogBroadcaster instance.
     */
    public function __construct(
        private string $name,
        private ?Logger $logger = null,
    ) {}

    /**
     * Runs broadcast.
     */
    public function broadcast(BroadcastMessage $message): BroadcastResult
    {
        $this->logger?->log(LogLevel::Info, 'Broadcast message sent.', [
            'broadcaster' => $this->name,
            'event' => $message->name(),
            'channels' => $message->channels(),
        ]);

        return new BroadcastResult($this->name, $message->name(), $message->channels());
    }
}
