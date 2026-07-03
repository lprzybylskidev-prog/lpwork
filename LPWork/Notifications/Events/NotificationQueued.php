<?php

declare(strict_types=1);

namespace LPWork\Notifications\Events;

/**
 * Represents the notification queued framework component.
 */
final readonly class NotificationQueued
{
    /**
     * Creates a new NotificationQueued instance.
     */
    public function __construct(
        public string $notification,
        public string $notifiable,
        public string $channel,
        public string $jobId,
    ) {}
}
