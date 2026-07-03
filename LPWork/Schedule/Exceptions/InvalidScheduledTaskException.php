<?php

declare(strict_types=1);

namespace LPWork\Schedule\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid scheduled task exception failures.
 */
final class InvalidScheduledTaskException extends InvalidArgumentException
{
    /**
     * Reports whether missing job handler.
     */
    public static function missingJobHandler(string $job): self
    {
        return new self(sprintf('Scheduled job [%s] must define a handle method.', $job));
    }

    /**
     * Performs the command not registered operation.
     */
    public static function commandNotRegistered(string $command): self
    {
        return new self(sprintf('Scheduled command [%s] is not registered.', $command));
    }
}
