<?php

declare(strict_types=1);

namespace LPWork\Broadcasting\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid broadcast message exception failures.
 */
final class InvalidBroadcastMessageException extends InvalidArgumentException
{
    /**
     * Reports whether missing channels.
     */
    public static function missingChannels(): self
    {
        return new self('Broadcast messages require at least one channel.');
    }

    /**
     * Reports whether missing name.
     */
    public static function missingName(): self
    {
        return new self('Broadcast messages require an event name.');
    }

    /**
     * Performs the invalid channel operation.
     */
    public static function invalidChannel(): self
    {
        return new self('Broadcast channel names cannot be empty.');
    }
}
