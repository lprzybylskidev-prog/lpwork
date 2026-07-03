<?php

declare(strict_types=1);

namespace LPWork\Broadcasting;

use Closure;
use LPWork\Broadcasting\Exceptions\BroadcastChannelNotFoundException;
use LPWork\Broadcasting\Exceptions\DuplicateBroadcastChannelException;

/**
 * Stores and resolves broadcast channel registry registrations.
 */
final class BroadcastChannelRegistry
{
    /**
     * @var array<string, BroadcastChannel>
     */
    private array $channels = [];

    /**
     * @param null|Closure(mixed): bool $authorizer
     */
    public function public(string $name, ?Closure $authorizer = null): void
    {
        $this->add(new BroadcastChannel($name, private: false, authorizer: $authorizer));
    }

    /**
     * @param null|Closure(mixed): bool $authorizer
     */
    public function private(string $name, ?Closure $authorizer = null): void
    {
        $this->add(new BroadcastChannel($name, private: true, authorizer: $authorizer));
    }

    /**
     * Returns the requested value from this component.
     */
    public function get(string $name): BroadcastChannel
    {
        if (!array_key_exists($name, $this->channels)) {
            throw new BroadcastChannelNotFoundException($name);
        }

        return $this->channels[$name];
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->channels);
    }

    private function add(BroadcastChannel $channel): void
    {
        if (array_key_exists($channel->name(), $this->channels)) {
            throw new DuplicateBroadcastChannelException($channel->name());
        }

        $this->channels[$channel->name()] = $channel;
    }
}
