<?php

declare(strict_types=1);

namespace LPWork\Broadcasting\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid broadcast channel exception failures.
 */
final class InvalidBroadcastChannelException extends InvalidArgumentException
{
    /**
     * Performs the empty name operation.
     */
    public static function emptyName(): self
    {
        return new self('Broadcast channel name cannot be empty.');
    }
}
