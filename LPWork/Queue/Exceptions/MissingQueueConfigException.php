<?php

declare(strict_types=1);

namespace LPWork\Queue\Exceptions;

use RuntimeException;

/**
 * Reports missing queue config exception failures.
 */
final class MissingQueueConfigException extends RuntimeException
{
    /**
     * Creates a new MissingQueueConfigException instance.
     */
    public function __construct(string $key)
    {
        parent::__construct(sprintf('Queue configuration value is missing: %s.', $key));
    }
}
