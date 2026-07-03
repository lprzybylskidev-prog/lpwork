<?php

declare(strict_types=1);

namespace LPWork\Http\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid cookie exception failures.
 */
final class InvalidCookieException extends InvalidArgumentException
{
    /**
     * Returns the configured name for this object.
     */
    public static function name(): self
    {
        return new self('Cookie name is invalid.');
    }

    /**
     * Performs the same site operation.
     */
    public static function sameSite(): self
    {
        return new self('Cookie SameSite value is invalid.');
    }
}
