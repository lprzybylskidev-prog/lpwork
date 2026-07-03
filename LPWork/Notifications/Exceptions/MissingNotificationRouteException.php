<?php

declare(strict_types=1);

namespace LPWork\Notifications\Exceptions;

use InvalidArgumentException;

/**
 * Reports missing notification route exception failures.
 */
final class MissingNotificationRouteException extends InvalidArgumentException
{
    /**
     * Performs the mail operation.
     */
    public static function mail(string $notifiable): self
    {
        return new self(sprintf('Notifiable [%s] does not define a mail notification route.', $notifiable));
    }

    /**
     * Returns database.
     */
    public static function database(string $notifiable): self
    {
        return new self(sprintf('Notifiable [%s] does not define a database notification route.', $notifiable));
    }

    /**
     * Runs broadcast.
     */
    public static function broadcast(string $notifiable): self
    {
        return new self(sprintf('Notifiable [%s] does not define broadcast notification channels.', $notifiable));
    }
}
