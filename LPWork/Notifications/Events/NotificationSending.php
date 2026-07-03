<?php

declare(strict_types=1);

namespace LPWork\Notifications\Events;

/**
 * Represents the notification sending framework component.
 */
final readonly class NotificationSending
{
    /**
     * Creates a new NotificationSending instance.
     */
    public function __construct(
        public string $notification,
        public string $notifiable,
        public string $channel,
    ) {}
}
