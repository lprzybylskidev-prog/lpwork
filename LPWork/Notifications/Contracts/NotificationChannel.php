<?php

declare(strict_types=1);

namespace LPWork\Notifications\Contracts;

use LPWork\Notifications\NotificationChannelResult;
use LPWork\Notifications\NotificationRoutes;

/**
 * Defines the contract for notification channel.
 */
interface NotificationChannel
{
    /**
     * Runs send.
     */
    public function send(Notifiable $notifiable, Notification $notification, NotificationRoutes $routes): NotificationChannelResult;
}
