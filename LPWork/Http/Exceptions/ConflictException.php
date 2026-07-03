<?php

declare(strict_types=1);

namespace LPWork\Http\Exceptions;

/**
 * Reports conflict exception failures.
 */
final class ConflictException extends HttpStatusException
{
    /**
     * Returns status code.
     */
    public function statusCode(): int
    {
        return 409;
    }
}
