<?php

declare(strict_types=1);

namespace LPWork\Notifications\Exceptions;

use InvalidArgumentException;

/**
 * Reports notification channel not found exception failures.
 */
final class NotificationChannelNotFoundException extends InvalidArgumentException
{
    /**
     * Creates a new NotificationChannelNotFoundException instance.
     */
    public function __construct(string $channel)
    {
        parent::__construct(sprintf('Notification channel [%s] is not registered.', $channel));
    }
}
