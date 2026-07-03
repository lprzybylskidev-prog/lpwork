<?php

declare(strict_types=1);

namespace LPWork\Events\Exceptions;

use InvalidArgumentException;

/**
 * Reports duplicate listener exception failures.
 */
final class DuplicateListenerException extends InvalidArgumentException
{
    /**
     * Creates a new DuplicateListenerException instance.
     */
    public function __construct(string $event, string $listener)
    {
        parent::__construct(sprintf('Listener [%s] is already registered for event [%s].', $listener, $event));
    }
}
