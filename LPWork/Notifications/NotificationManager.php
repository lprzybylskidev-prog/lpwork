<?php

declare(strict_types=1);

namespace LPWork\Notifications;

use LPWork\Events\EventDispatcher;
use LPWork\Notifications\Contracts\Notifiable;
use LPWork\Notifications\Contracts\Notification;
use LPWork\Notifications\Contracts\QueueableNotifiable;
use LPWork\Notifications\Contracts\ShouldQueueNotification;
use LPWork\Notifications\Events\NotificationFailed;
use LPWork\Notifications\Events\NotificationQueued;
use LPWork\Notifications\Events\NotificationSending;
use LPWork\Notifications\Events\NotificationSent;
use LPWork\Notifications\Exceptions\InvalidNotifiableException;
use LPWork\Notifications\Jobs\SendQueuedNotification;
use LPWork\Queue\QueueManager;
use Throwable;

/**
 * Coordinates configured notification manager services.
 */
final readonly class NotificationManager
{
    /**
     * Creates a new NotificationManager instance.
     */
    public function __construct(
        private NotificationChannelRegistry $channels,
        private ?QueueManager $queue = null,
        private ?EventDispatcher $events = null,
    ) {}

    /**
     * Runs send.
     */
    public function send(Notifiable $notifiable, Notification $notification, ?NotificationSendOptions $options = null): NotificationSendResult
    {
        $options ??= new NotificationSendOptions();
        $channels = $options->channels ?? $notification->channels($notifiable);

        if ($channels === []) {
            throw InvalidNotifiableException::missingChannels($notification::class);
        }

        if ($options->queue && $notification instanceof ShouldQueueNotification && $this->queue !== null) {
            return $this->queue($notifiable, $notification, $channels);
        }

        return $this->sendNow($notifiable, $notification, $channels);
    }

    /**
     * @param list<string> $channels
     */
    public function sendNow(Notifiable $notifiable, Notification $notification, array $channels): NotificationSendResult
    {
        $routes = $notifiable->notificationRoutes();
        $results = [];

        foreach ($channels as $channel) {
            $this->events?->dispatch(new NotificationSending($notification::class, $notifiable::class, $channel));

            try {
                $result = $this->channels->get($channel)->send($notifiable, $notification, $routes);
            } catch (Throwable $throwable) {
                $this->events?->dispatch(new NotificationFailed($notification::class, $notifiable::class, $channel, $throwable::class));

                throw $throwable;
            }

            $this->events?->dispatch(new NotificationSent($notification::class, $notifiable::class, $channel, $result->status));
            $results[] = $result;
        }

        return new NotificationSendResult($notification::class, $notifiable::class, $results);
    }

    /**
     * @param list<string> $channels
     */
    private function queue(Notifiable $notifiable, ShouldQueueNotification $notification, array $channels): NotificationSendResult
    {
        if (!$notifiable instanceof QueueableNotifiable) {
            throw InvalidNotifiableException::cannotQueue($notifiable::class);
        }

        $payload = $notifiable->queuedNotificationPayload();
        $queued = [];

        foreach ($channels as $channel) {
            $id = $this->queue?->dispatch(
                new SendQueuedNotification($payload, $notification, [$channel]),
                $notification->queueOptions()->toQueueDispatchOptions(),
            );

            $this->events?->dispatch(new NotificationQueued($notification::class, $notifiable::class, $channel, (string) $id));
            $queued[] = new NotificationChannelResult($channel, 'queued', ['job_id' => (string) $id]);
        }

        return new NotificationSendResult($notification::class, $notifiable::class, $queued);
    }
}
