<?php

declare(strict_types=1);

namespace LPWork\Notifications\Contracts;

/**
 * Defines the contract for notification.
 */
interface Notification
{
    /**
     * @return list<string>
     */
    public function channels(object $notifiable): array;
}
