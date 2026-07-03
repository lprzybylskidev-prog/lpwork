<?php

declare(strict_types=1);

namespace LPWork\Notifications\Channels;

use LPWork\Notifications\Contracts\DatabaseNotification;
use LPWork\Notifications\Contracts\Notifiable;
use LPWork\Notifications\Contracts\Notification;
use LPWork\Notifications\Contracts\NotificationChannel;
use LPWork\Notifications\Exceptions\InvalidNotificationChannelException;
use LPWork\Notifications\Exceptions\MissingNotificationRouteException;
use LPWork\Notifications\NotificationChannelResult;
use LPWork\Notifications\NotificationDatabaseRepository;
use LPWork\Notifications\NotificationRoutes;
use LPWork\Time\Contracts\Clock;

/**
 * Represents the database notification channel framework component.
 */
final readonly class DatabaseNotificationChannel implements NotificationChannel
{
    /**
     * Creates a new DatabaseNotificationChannel instance.
     */
    public function __construct(
        private NotificationDatabaseRepository $repository,
        private Clock $clock,
    ) {}

    /**
     * Runs send.
     */
    public function send(Notifiable $notifiable, Notification $notification, NotificationRoutes $routes): NotificationChannelResult
    {
        if (!$notification instanceof DatabaseNotification) {
            throw InvalidNotificationChannelException::unsupportedNotification('database', $notification::class, DatabaseNotification::class);
        }

        $notifiableId = $routes->databaseId();

        if ($notifiableId === null) {
            throw MissingNotificationRouteException::database($notifiable::class);
        }

        $id = $this->repository->store(
            notifiableType: $routes->databaseType() ?? $notifiable::class,
            notifiableId: $notifiableId,
            notification: $notification::class,
            data: $notification->toDatabase($notifiable),
            now: $this->clock->now()->getTimestamp(),
        );

        return new NotificationChannelResult('database', 'sent', ['id' => $id]);
    }
}
