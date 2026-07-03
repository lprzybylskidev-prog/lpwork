<?php

declare(strict_types=1);

namespace LPWork\Queue\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid queue driver exception failures.
 */
final class InvalidQueueDriverException extends InvalidArgumentException
{
    /**
     * Creates a new InvalidQueueDriverException instance.
     */
    public function __construct(string $driver)
    {
        parent::__construct(sprintf('Queue driver is not supported: %s.', $driver));
    }
}
