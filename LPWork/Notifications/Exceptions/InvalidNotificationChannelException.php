<?php

declare(strict_types=1);

namespace LPWork\Notifications\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid notification channel exception failures.
 */
final class InvalidNotificationChannelException extends InvalidArgumentException
{
    /**
     * Performs the unsupported notification operation.
     */
    public static function unsupportedNotification(string $channel, string $notification, string $contract): self
    {
        return new self(sprintf('Notification [%s] cannot be sent through [%s]; it must implement [%s].', $notification, $channel, $contract));
    }
}
