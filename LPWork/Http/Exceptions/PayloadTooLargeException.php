<?php

declare(strict_types=1);

namespace LPWork\Http\Exceptions;

/**
 * Reports payload too large exception failures.
 */
final class PayloadTooLargeException extends HttpStatusException
{
    /**
     * Returns status code.
     */
    public function statusCode(): int
    {
        return 413;
    }
}
