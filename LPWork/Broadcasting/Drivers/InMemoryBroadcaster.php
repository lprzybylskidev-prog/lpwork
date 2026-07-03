<?php

declare(strict_types=1);

namespace LPWork\Broadcasting\Drivers;

use LPWork\Broadcasting\BroadcastMessage;
use LPWork\Broadcasting\BroadcastResult;
use LPWork\Broadcasting\Contracts\Broadcaster;

/**
 * Represents the in memory broadcaster framework component.
 */
final class InMemoryBroadcaster implements Broadcaster
{
    /**
     * @var list<BroadcastMessage>
     */
    private array $messages = [];

    /**
     * Creates a new InMemoryBroadcaster instance.
     */
    public function __construct(
        private readonly string $name,
    ) {}

    /**
     * Runs broadcast.
     */
    public function broadcast(BroadcastMessage $message): BroadcastResult
    {
        $this->messages[] = $message;

        return new BroadcastResult($this->name, $message->name(), $message->channels());
    }

    /**
     * @return list<BroadcastMessage>
     */
    public function messages(): array
    {
        return $this->messages;
    }
}
