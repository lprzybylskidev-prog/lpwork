<?php

declare(strict_types=1);

namespace LPWork\Notifications;

/**
 * Represents the queued notifiable payload framework component.
 */
final readonly class QueuedNotifiablePayload
{
    /**
     * Creates a new QueuedNotifiablePayload instance.
     */
    public function __construct(
        public string $notifiableClass,
        public NotificationRoutes $routes,
    ) {}
}
