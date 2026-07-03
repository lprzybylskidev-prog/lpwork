<?php

declare(strict_types=1);

namespace LPWork\Notifications\Events;

/**
 * Represents the notification sent framework component.
 */
final readonly class NotificationSent
{
    /**
     * Creates a new NotificationSent instance.
     */
    public function __construct(
        public string $notification,
        public string $notifiable,
        public string $channel,
        public string $status,
    ) {}
}
