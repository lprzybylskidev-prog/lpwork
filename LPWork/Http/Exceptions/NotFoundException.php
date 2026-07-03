<?php

declare(strict_types=1);

namespace LPWork\Http\Exceptions;

/**
 * Reports not found exception failures.
 */
final class NotFoundException extends HttpStatusException
{
    /**
     * Returns status code.
     */
    public function statusCode(): int
    {
        return 404;
    }
}
