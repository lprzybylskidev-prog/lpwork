<?php

declare(strict_types=1);

namespace LPWork\Frontend\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid frontend command exception failures.
 */
final class InvalidFrontendCommandException extends InvalidArgumentException
{
    /**
     * Performs the empty script name operation.
     */
    public static function emptyScriptName(): self
    {
        return new self('Frontend package manager script name must not be empty.');
    }
}
