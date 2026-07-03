<?php

declare(strict_types=1);

namespace LPWork\Notifications;

use LPWork\Notifications\Contracts\NotificationChannel;
use LPWork\Notifications\Exceptions\DuplicateNotificationChannelException;
use LPWork\Notifications\Exceptions\NotificationChannelNotFoundException;

/**
 * Stores and resolves notification channel registry registrations.
 */
final class NotificationChannelRegistry
{
    /**
     * @var array<string, NotificationChannel>
     */
    private array $channels = [];

    /**
     * Adds an item to this component's registry or backing store.
     */
    public function add(string $name, NotificationChannel $channel): void
    {
        if ($name === '') {
            throw new NotificationChannelNotFoundException($name);
        }

        if (array_key_exists($name, $this->channels)) {
            throw new DuplicateNotificationChannelException($name);
        }

        $this->channels[$name] = $channel;
    }

    /**
     * Returns the requested value from this component.
     */
    public function get(string $name): NotificationChannel
    {
        if (!array_key_exists($name, $this->channels)) {
            throw new NotificationChannelNotFoundException($name);
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
}
