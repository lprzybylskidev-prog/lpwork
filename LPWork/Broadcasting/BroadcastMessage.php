<?php

declare(strict_types=1);

namespace LPWork\Broadcasting;

use LPWork\Broadcasting\Exceptions\InvalidBroadcastMessageException;

/**
 * Represents the broadcast message framework component.
 */
final readonly class BroadcastMessage
{
    /**
     * @param list<string> $channels
     * @param array<string, mixed> $payload
     */
    public function __construct(
        private array $channels,
        private string $name,
        private array $payload = [],
    ) {
        if ($this->channels === []) {
            throw InvalidBroadcastMessageException::missingChannels();
        }

        if ($this->name === '') {
            throw InvalidBroadcastMessageException::missingName();
        }

        foreach ($this->channels as $channel) {
            if ($channel === '') {
                throw InvalidBroadcastMessageException::invalidChannel();
            }
        }
    }

    /**
     * @return list<string>
     */
    public function channels(): array
    {
        return $this->channels;
    }

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }
}
