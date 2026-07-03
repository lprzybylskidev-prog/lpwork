<?php

declare(strict_types=1);

namespace LPWork\Notifications\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Reports invalid notification storage exception failures.
 */
final class InvalidNotificationStorageException extends RuntimeException
{
    /**
     * Performs the invalid payload operation.
     */
    public static function invalidPayload(Throwable $throwable): self
    {
        return new self('Notification database payload could not be encoded or decoded.', previous: $throwable);
    }

    /**
     * Performs the invalid record operation.
     */
    public static function invalidRecord(): self
    {
        return new self('Notification database record is invalid.');
    }
}
