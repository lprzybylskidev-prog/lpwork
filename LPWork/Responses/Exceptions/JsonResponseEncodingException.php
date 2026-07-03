<?php

declare(strict_types=1);

namespace LPWork\Responses\Exceptions;

use JsonException;
use RuntimeException;

/**
 * Reports json response encoding exception failures.
 */
final class JsonResponseEncodingException extends RuntimeException
{
    /**
     * Performs the for previous operation.
     */
    public static function forPrevious(JsonException $exception): self
    {
        return new self('The HTTP JSON response could not be encoded.', previous: $exception);
    }
}
