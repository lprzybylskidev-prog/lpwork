<?php

declare(strict_types=1);

namespace LPWork\Notifications\Channels;

use LPWork\Broadcasting\BroadcastManager;
use LPWork\Broadcasting\BroadcastMessage;
use LPWork\Notifications\Contracts\BroadcastNotification;
use LPWork\Notifications\Contracts\Notifiable;
use LPWork\Notifications\Contracts\Notification;
use LPWork\Notifications\Contracts\NotificationChannel;
use LPWork\Notifications\Exceptions\InvalidNotificationChannelException;
use LPWork\Notifications\Exceptions\MissingNotificationRouteException;
use LPWork\Notifications\NotificationChannelResult;
use LPWork\Notifications\NotificationRoutes;

/**
 * Represents the broadcast notification channel framework component.
 */
final readonly class BroadcastNotificationChannel implements NotificationChannel
{
    /**
     * Creates a new BroadcastNotificationChannel instance.
     */
    public function __construct(
        private BroadcastManager $broadcasts,
    ) {}

    /**
     * Runs send.
     */
    public function send(Notifiable $notifiable, Notification $notification, NotificationRoutes $routes): NotificationChannelResult
    {
        if (!$notification instanceof BroadcastNotification) {
            throw InvalidNotificationChannelException::unsupportedNotification('broadcast', $notification::class, BroadcastNotification::class);
        }

        $channels = $routes->broadcastChannels();

        if ($channels === []) {
            throw MissingNotificationRouteException::broadcast($notifiable::class);
        }

        $result = $this->broadcasts->broadcast(new BroadcastMessage(
            channels: $channels,
            name: $notification->broadcastName($notifiable),
            payload: $notification->toBroadcast($notifiable),
        ));

        return new NotificationChannelResult('broadcast', 'sent', [
            'broadcaster' => $result->driver,
            'event' => $result->event,
        ]);
    }
}
