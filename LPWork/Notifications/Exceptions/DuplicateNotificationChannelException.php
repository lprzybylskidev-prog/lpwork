<?php

declare(strict_types=1);

namespace LPWork\Notifications\Exceptions;

use InvalidArgumentException;

/**
 * Reports duplicate notification channel exception failures.
 */
final class DuplicateNotificationChannelException extends InvalidArgumentException
{
    /**
     * Creates a new DuplicateNotificationChannelException instance.
     */
    public function __construct(string $channel)
    {
        parent::__construct(sprintf('Notification channel [%s] is already registered.', $channel));
    }
}
