<?php

declare(strict_types=1);

namespace LPWork\Mail\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Reports mail transport exception failures.
 */
final class MailTransportException extends RuntimeException
{
    /**
     * Returns connection failed.
     */
    public static function connectionFailed(string $transport): self
    {
        return new self(sprintf('Could not connect to mail transport [%s].', $transport));
    }

    /**
     * Performs the unexpected response operation.
     */
    public static function unexpectedResponse(string $transport, string $response): self
    {
        return new self(sprintf('Mail transport [%s] returned an unexpected response: %s', $transport, $response));
    }

    /**
     * Registers or stores write failed.
     */
    public static function writeFailed(string $transport): self
    {
        return new self(sprintf('Could not write to mail transport [%s].', $transport));
    }

    /**
     * Runs send failed.
     */
    public static function sendFailed(string $transport, Throwable $previous): self
    {
        return new self(sprintf('Could not send mail through transport [%s].', $transport), previous: $previous);
    }
}
