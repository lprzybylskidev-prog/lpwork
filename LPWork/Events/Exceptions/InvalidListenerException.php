<?php

declare(strict_types=1);

namespace LPWork\Events\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid listener exception failures.
 */
final class InvalidListenerException extends InvalidArgumentException
{
    /**
     * Reports whether missing handle.
     */
    public static function missingHandle(string $listener): self
    {
        return new self(sprintf('Listener [%s] must define a handle method.', $listener));
    }

    /**
     * Performs the not callable operation.
     */
    public static function notCallable(string $listener): self
    {
        return new self(sprintf('Listener [%s] must be callable.', $listener));
    }
}
