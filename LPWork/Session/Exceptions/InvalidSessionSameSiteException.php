<?php

declare(strict_types=1);

namespace LPWork\Session\Exceptions;

use RuntimeException;

/**
 * Reports invalid session same site exception failures.
 */
final class InvalidSessionSameSiteException extends RuntimeException
{
    /**
     * Creates a new InvalidSessionSameSiteException instance.
     */
    public function __construct(string $sameSite)
    {
        parent::__construct("Session same_site value is not supported: {$sameSite}.");
    }
}
