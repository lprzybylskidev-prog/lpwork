<?php

declare(strict_types=1);

namespace LPWork\Notifications\Events;

/**
 * Represents the notification failed framework component.
 */
final readonly class NotificationFailed
{
    /**
     * Creates a new NotificationFailed instance.
     */
    public function __construct(
        public string $notification,
        public string $notifiable,
        public string $channel,
        public string $exception,
    ) {}
}
