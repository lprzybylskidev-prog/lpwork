<?php

declare(strict_types=1);

namespace LPWork\Broadcasting\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid broadcaster exception failures.
 */
final class InvalidBroadcasterException extends InvalidArgumentException
{
    /**
     * Creates a new InvalidBroadcasterException instance.
     */
    public function __construct(string $broadcaster)
    {
        parent::__construct(sprintf('Broadcast broadcaster [%s] is not configured.', $broadcaster));
    }
}
