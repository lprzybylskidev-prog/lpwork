<?php

declare(strict_types=1);

namespace LPWork\Maintenance\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Reports invalid maintenance state exception failures.
 */
final class InvalidMaintenanceStateException extends RuntimeException
{
    /**
     * Performs the for unreadable payload operation.
     */
    public static function forUnreadablePayload(string $path, ?Throwable $previous = null): self
    {
        return new self(sprintf('Maintenance state file [%s] does not contain a valid payload.', $path), previous: $previous);
    }

    /**
     * Performs the for unwritable payload operation.
     */
    public static function forUnwritablePayload(string $path, Throwable $previous): self
    {
        return new self(sprintf('Maintenance state file [%s] could not be encoded.', $path), previous: $previous);
    }
}
