<?php

declare(strict_types=1);

namespace LPWork\Notifications\Contracts;

/**
 * Defines the contract for broadcast notification.
 */
interface BroadcastNotification extends Notification
{
    /**
     * @return array<string, mixed>
     */
    public function toBroadcast(Notifiable $notifiable): array;

    /**
     * Runs broadcast name.
     */
    public function broadcastName(Notifiable $notifiable): string;
}
