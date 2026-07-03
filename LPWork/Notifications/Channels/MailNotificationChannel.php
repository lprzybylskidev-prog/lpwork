<?php

declare(strict_types=1);

namespace LPWork\Notifications\Channels;

use LPWork\Mail\MailManager;
use LPWork\Mail\MailSendOptions;
use LPWork\Notifications\Contracts\MailNotification;
use LPWork\Notifications\Contracts\Notifiable;
use LPWork\Notifications\Contracts\Notification;
use LPWork\Notifications\Contracts\NotificationChannel;
use LPWork\Notifications\Exceptions\InvalidNotificationChannelException;
use LPWork\Notifications\Exceptions\MissingNotificationRouteException;
use LPWork\Notifications\NotificationChannelResult;
use LPWork\Notifications\NotificationRoutes;

/**
 * Represents the mail notification channel framework component.
 */
final readonly class MailNotificationChannel implements NotificationChannel
{
    /**
     * Creates a new MailNotificationChannel instance.
     */
    public function __construct(
        private MailManager $mail,
    ) {}

    /**
     * Runs send.
     */
    public function send(Notifiable $notifiable, Notification $notification, NotificationRoutes $routes): NotificationChannelResult
    {
        if (!$notification instanceof MailNotification) {
            throw InvalidNotificationChannelException::unsupportedNotification('mail', $notification::class, MailNotification::class);
        }

        $recipient = $routes->mailRoute();

        if ($recipient === null) {
            throw MissingNotificationRouteException::mail($notifiable::class);
        }

        $message = $notification->toMail($notifiable);

        if ($message->toAddresses() === []) {
            $message = $message->to($recipient);
        }

        $result = $this->mail->send($message, new MailSendOptions());

        return new NotificationChannelResult('mail', 'sent', [
            'transport' => $result->transport,
            'message_id' => $result->messageId,
        ]);
    }
}
