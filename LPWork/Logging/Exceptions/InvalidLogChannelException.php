<?php

declare(strict_types=1);

namespace LPWork\Logging\Exceptions;

use RuntimeException;

/**
 * Reports invalid log channel exception failures.
 */
final class InvalidLogChannelException extends RuntimeException
{
    /**
     * Creates a new InvalidLogChannelException instance.
     */
    public function __construct(string $channel)
    {
        parent::__construct("Log channel is not configured: {$channel}.");
    }
}
