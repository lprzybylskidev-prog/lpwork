<?php

declare(strict_types=1);

namespace LPWork\Notifications\Contracts;

use LPWork\Notifications\QueuedNotifiablePayload;

/**
 * Defines the contract for queueable notifiable.
 */
interface QueueableNotifiable extends Notifiable
{
    /**
     * Returns queued notification payload.
     */
    public function queuedNotificationPayload(): QueuedNotifiablePayload;
}
