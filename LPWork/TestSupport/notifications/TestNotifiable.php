<?php

declare(strict_types=1);

namespace Tests\support\notifications;

use LPWork\Notifications\Contracts\QueueableNotifiable;
use LPWork\Notifications\NotificationRoutes;
use LPWork\Notifications\QueuedNotifiablePayload;

final readonly class TestNotifiable implements QueueableNotifiable
{
    public function __construct(
        private string $id = '42',
    ) {}

    public function notificationRoutes(): NotificationRoutes
    {
        return NotificationRoutes::create()
            ->mail('ada@example.test', 'Ada')
            ->database($this->id, 'user')
            ->broadcast(['users.' . $this->id]);
    }

    public function queuedNotificationPayload(): QueuedNotifiablePayload
    {
        return new QueuedNotifiablePayload(self::class, $this->notificationRoutes());
    }
}
