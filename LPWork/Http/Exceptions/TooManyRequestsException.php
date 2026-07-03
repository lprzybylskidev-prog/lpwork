<?php

declare(strict_types=1);

namespace LPWork\Http\Exceptions;

/**
 * Reports too many requests exception failures.
 */
final class TooManyRequestsException extends HttpStatusException
{
    /**
     * Returns a copy with with retry after applied.
     */
    public static function withRetryAfter(string $retryAfter, string $message = ''): self
    {
        return new self($message, ['Retry-After' => $retryAfter]);
    }

    /**
     * Returns status code.
     */
    public function statusCode(): int
    {
        return 429;
    }
}
