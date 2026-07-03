<?php

declare(strict_types=1);

namespace LPWork\Broadcasting\Drivers;

use LPWork\Broadcasting\BroadcastMessage;
use LPWork\Broadcasting\BroadcastResult;
use LPWork\Broadcasting\Contracts\Broadcaster;
use LPWork\Shared\Redis\RedisClient;

/**
 * Represents the redis broadcaster framework component.
 */
final readonly class RedisBroadcaster implements Broadcaster
{
    /**
     * Creates a new RedisBroadcaster instance.
     */
    public function __construct(
        private string $name,
        private RedisClient $redis,
    ) {}

    /**
     * Runs broadcast.
     */
    public function broadcast(BroadcastMessage $message): BroadcastResult
    {
        $payload = json_encode([
            'event' => $message->name(),
            'channels' => $message->channels(),
            'payload' => $message->payload(),
        ], JSON_THROW_ON_ERROR);

        foreach ($message->channels() as $channel) {
            $this->redis->publish('broadcast:' . $channel, $payload);
        }

        return new BroadcastResult($this->name, $message->name(), $message->channels());
    }
}
