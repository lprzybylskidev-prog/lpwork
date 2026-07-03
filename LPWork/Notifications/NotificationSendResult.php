<?php

declare(strict_types=1);

namespace LPWork\Notifications;

/**
 * Represents the result of notification send result work.
 */
final readonly class NotificationSendResult
{
    /**
     * @param list<NotificationChannelResult> $channels
     */
    public function __construct(
        public string $notification,
        public string $notifiable,
        public array $channels,
    ) {}
}
