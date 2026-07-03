<?php

declare(strict_types=1);

namespace LPWork\Notifications\Contracts;

use LPWork\Notifications\NotificationQueueOptions;

/**
 * Defines the contract for should queue notification.
 */
interface ShouldQueueNotification extends Notification
{
    /**
     * Returns queue options.
     */
    public function queueOptions(): NotificationQueueOptions;
}
