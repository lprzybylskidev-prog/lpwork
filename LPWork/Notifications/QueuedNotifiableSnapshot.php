<?php

declare(strict_types=1);

namespace LPWork\Notifications;

use LPWork\Notifications\Contracts\Notifiable;

/**
 * Represents the queued notifiable snapshot framework component.
 */
final readonly class QueuedNotifiableSnapshot implements Notifiable
{
    /**
     * Creates a new QueuedNotifiableSnapshot instance.
     */
    public function __construct(
        private QueuedNotifiablePayload $payload,
    ) {}

    /**
     * Performs the notification routes operation.
     */
    public function notificationRoutes(): NotificationRoutes
    {
        return $this->payload->routes;
    }

    /**
     * Performs the notifiable class operation.
     */
    public function notifiableClass(): string
    {
        return $this->payload->notifiableClass;
    }
}
