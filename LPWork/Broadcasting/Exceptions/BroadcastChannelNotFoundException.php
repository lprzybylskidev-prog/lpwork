<?php

declare(strict_types=1);

namespace LPWork\Broadcasting\Exceptions;

use InvalidArgumentException;

/**
 * Reports broadcast channel not found exception failures.
 */
final class BroadcastChannelNotFoundException extends InvalidArgumentException
{
    /**
     * Creates a new BroadcastChannelNotFoundException instance.
     */
    public function __construct(string $channel)
    {
        parent::__construct(sprintf('Broadcast channel [%s] is not registered.', $channel));
    }
}
