<?php

declare(strict_types=1);

namespace LPWork\Broadcasting\Exceptions;

use InvalidArgumentException;

/**
 * Reports duplicate broadcast channel exception failures.
 */
final class DuplicateBroadcastChannelException extends InvalidArgumentException
{
    /**
     * Creates a new DuplicateBroadcastChannelException instance.
     */
    public function __construct(string $channel)
    {
        parent::__construct(sprintf('Broadcast channel [%s] is already registered.', $channel));
    }
}
