<?php

declare(strict_types=1);

namespace LPWork\Notifications\Contracts;

/**
 * Defines the contract for database notification.
 */
interface DatabaseNotification extends Notification
{
    /**
     * @return array<string, mixed>
     */
    public function toDatabase(Notifiable $notifiable): array;
}
