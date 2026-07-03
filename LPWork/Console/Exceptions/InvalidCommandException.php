<?php

declare(strict_types=1);

namespace LPWork\Console\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid command exception failures.
 */
final class InvalidCommandException extends InvalidArgumentException
{
    /**
     * Performs the class does not implement command operation.
     */
    public static function classDoesNotImplementCommand(string $command): self
    {
        return new self(sprintf(
            'Console command [%s] must implement the Command contract.',
            $command,
        ));
    }
}
