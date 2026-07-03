<?php

declare(strict_types=1);

namespace LPWork\Notifications\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid notifiable exception failures.
 */
final class InvalidNotifiableException extends InvalidArgumentException
{
    /**
     * Reports whether missing channels.
     */
    public static function missingChannels(string $notification): self
    {
        return new self(sprintf('Notification [%s] did not declare any delivery channels.', $notification));
    }

    /**
     * Reports whether cannot queue.
     */
    public static function cannotQueue(string $notifiable): self
    {
        return new self(sprintf('Notifiable [%s] must implement QueueableNotifiable before it can be queued.', $notifiable));
    }
}
