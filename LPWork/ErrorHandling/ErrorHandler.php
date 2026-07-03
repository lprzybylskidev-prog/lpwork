<?php

declare(strict_types=1);

namespace LPWork\ErrorHandling;

use ErrorException;

/**
 * Represents the error handler framework component.
 */
final class ErrorHandler
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(): void
    {
        set_error_handler([$this, 'handle']);
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(
        int $severity,
        string $message,
        string $file,
        int $line
    ): bool {
        if ((error_reporting() & $severity) === 0) {
            return false;
        }

        throw new ErrorException(
            message: $message,
            code: 0,
            severity: $severity,
            filename: $file,
            line: $line
        );
    }
}
