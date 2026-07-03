<?php

declare(strict_types=1);

namespace LPWork\Broadcasting\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid broadcasting driver exception failures.
 */
final class InvalidBroadcastingDriverException extends InvalidArgumentException
{
    /**
     * Creates a new InvalidBroadcastingDriverException instance.
     */
    public function __construct(string $driver)
    {
        parent::__construct(sprintf('Broadcasting driver [%s] is not supported.', $driver));
    }
}
