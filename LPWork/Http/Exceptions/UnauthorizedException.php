<?php

declare(strict_types=1);

namespace LPWork\Http\Exceptions;

/**
 * Reports unauthorized exception failures.
 */
final class UnauthorizedException extends HttpStatusException
{
    /**
     * Returns a copy with with authenticate header applied.
     */
    public static function withAuthenticateHeader(string $value, string $message = ''): self
    {
        return new self($message, ['WWW-Authenticate' => $value]);
    }

    /**
     * Returns status code.
     */
    public function statusCode(): int
    {
        return 401;
    }
}
