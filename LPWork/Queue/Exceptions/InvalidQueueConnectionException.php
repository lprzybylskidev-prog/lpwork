<?php

declare(strict_types=1);

namespace LPWork\Queue\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid queue connection exception failures.
 */
final class InvalidQueueConnectionException extends InvalidArgumentException
{
    /**
     * Creates a new InvalidQueueConnectionException instance.
     */
    public function __construct(string $connection)
    {
        parent::__construct(sprintf('Queue connection is not configured: %s.', $connection));
    }
}
