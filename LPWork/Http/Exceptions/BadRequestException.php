<?php

declare(strict_types=1);

namespace LPWork\Http\Exceptions;

/**
 * Reports bad request exception failures.
 */
final class BadRequestException extends HttpStatusException
{
    /**
     * Returns status code.
     */
    public function statusCode(): int
    {
        return 400;
    }
}
