<?php

declare(strict_types=1);

namespace LPWork\Http\Exceptions;

/**
 * Reports forbidden exception failures.
 */
final class ForbiddenException extends HttpStatusException
{
    /**
     * Returns status code.
     */
    public function statusCode(): int
    {
        return 403;
    }
}
