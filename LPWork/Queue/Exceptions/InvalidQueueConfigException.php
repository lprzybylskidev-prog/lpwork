<?php

declare(strict_types=1);

namespace LPWork\Queue\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid queue config exception failures.
 */
final class InvalidQueueConfigException extends InvalidArgumentException
{
    /**
     * Creates a new InvalidQueueConfigException instance.
     */
    public function __construct(string $key)
    {
        parent::__construct(sprintf('Queue configuration value is invalid: %s.', $key));
    }
}
