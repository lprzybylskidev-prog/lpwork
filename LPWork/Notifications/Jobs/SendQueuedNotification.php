<?php

declare(strict_types=1);

namespace LPWork\Notifications\Jobs;

use LPWork\Notifications\Contracts\Notification;
use LPWork\Notifications\NotificationManager;
use LPWork\Notifications\QueuedNotifiablePayload;
use LPWork\Notifications\QueuedNotifiableSnapshot;

/**
 * Represents the send queued notification framework component.
 */
final readonly class SendQueuedNotification
{
    /**
     * @param list<string> $channels
     */
    public function __construct(
        private QueuedNotifiablePayload $notifiable,
        private Notification $notification,
        private array $channels,
    ) {}

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(NotificationManager $notifications): void
    {
        $notifications->sendNow(new QueuedNotifiableSnapshot($this->notifiable), $this->notification, $this->channels);
    }
}
