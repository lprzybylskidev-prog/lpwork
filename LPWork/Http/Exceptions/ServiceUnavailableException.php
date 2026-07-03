<?php

declare(strict_types=1);

namespace LPWork\Http\Exceptions;

/**
 * Reports service unavailable exception failures.
 */
final class ServiceUnavailableException extends HttpStatusException
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
        return 503;
    }
}
