<?php

declare(strict_types=1);

namespace Tests\support\notifications;

use LPWork\Mail\MailMessage;
use LPWork\Notifications\Contracts\BroadcastNotification;
use LPWork\Notifications\Contracts\DatabaseNotification;
use LPWork\Notifications\Contracts\MailNotification;
use LPWork\Notifications\Contracts\Notifiable;
use LPWork\Notifications\Contracts\ShouldQueueNotification;
use LPWork\Notifications\NotificationQueueOptions;

final readonly class WelcomeNotification implements MailNotification, DatabaseNotification, BroadcastNotification, ShouldQueueNotification
{
    /**
     * @return list<string>
     */
    public function channels(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail(Notifiable $notifiable): MailMessage
    {
        return MailMessage::create()
            ->subject('Welcome')
            ->text('Hello');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(Notifiable $notifiable): array
    {
        return ['message' => 'Welcome'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toBroadcast(Notifiable $notifiable): array
    {
        return ['message' => 'Welcome'];
    }

    public function broadcastName(Notifiable $notifiable): string
    {
        return 'notification.welcome';
    }

    public function queueOptions(): NotificationQueueOptions
    {
        return new NotificationQueueOptions(connection: 'sync', queue: 'notifications');
    }
}
