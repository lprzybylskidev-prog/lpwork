<?php

declare(strict_types=1);

namespace LPWork\Notifications\Contracts;

use LPWork\Notifications\NotificationRoutes;

/**
 * Defines the contract for notifiable.
 */
interface Notifiable
{
    /**
     * Performs the notification routes operation.
     */
    public function notificationRoutes(): NotificationRoutes;
}
