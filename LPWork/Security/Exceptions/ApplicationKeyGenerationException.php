<?php

declare(strict_types=1);

namespace LPWork\Security\Exceptions;

use Random\RandomException;
use RuntimeException;

/**
 * Reports application key generation exception failures.
 */
final class ApplicationKeyGenerationException extends RuntimeException
{
    /**
     * Performs the for random failure operation.
     */
    public static function forRandomFailure(RandomException $exception): self
    {
        return new self('Could not generate a secure APP_KEY.', previous: $exception);
    }
}
