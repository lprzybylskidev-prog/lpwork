<?php

declare(strict_types=1);

namespace LPWork\Http\Exceptions;

/**
 * Reports unprocessable entity exception failures.
 */
final class UnprocessableEntityException extends HttpStatusException
{
    /**
     * Returns status code.
     */
    public function statusCode(): int
    {
        return 422;
    }
}
