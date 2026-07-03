<?php

declare(strict_types=1);

namespace LPWork\Security\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Reports csrf token generation exception failures.
 */
final class CsrfTokenGenerationException extends RuntimeException
{
    /**
     * Performs the for previous operation.
     */
    public static function forPrevious(Throwable $exception): self
    {
        return new self('Could not generate a CSRF token.', previous: $exception);
    }
}
