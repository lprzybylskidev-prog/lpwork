<?php

declare(strict_types=1);

namespace LPWork\Http\Exceptions;

/**
 * Reports gone exception failures.
 */
final class GoneException extends HttpStatusException
{
    /**
     * Returns status code.
     */
    public function statusCode(): int
    {
        return 410;
    }
}
