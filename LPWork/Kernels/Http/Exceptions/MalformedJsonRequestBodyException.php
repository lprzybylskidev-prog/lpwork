<?php

declare(strict_types=1);

namespace LPWork\Kernels\Http\Exceptions;

use JsonException;
use LPWork\Http\Exceptions\HttpStatusException;

/**
 * Reports malformed json request body exception failures.
 */
final class MalformedJsonRequestBodyException extends HttpStatusException
{
    /**
     * Performs the for previous operation.
     */
    public static function forPrevious(JsonException $exception): self
    {
        return new self('The HTTP JSON request body is malformed.', previous: $exception);
    }

    /**
     * Performs the for non object body operation.
     */
    public static function forNonObjectBody(): self
    {
        return new self('The HTTP JSON request body must be an object.');
    }

    /**
     * Returns status code.
     */
    public function statusCode(): int
    {
        return 400;
    }
}
