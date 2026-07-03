<?php

declare(strict_types=1);

namespace LPWork\Security\Exceptions;

use RuntimeException;

/**
 * Reports invalid application key exception failures.
 */
final class InvalidApplicationKeyException extends RuntimeException
{
    public static function empty(): self
    {
        return new self('APP_KEY must not be empty.');
    }

    /**
     * Performs the malformed base64 operation.
     */
    public static function malformedBase64(): self
    {
        return new self('APP_KEY base64 value is malformed.');
    }

    /**
     * Converts this value to too short output.
     */
    public static function tooShort(int $minimumBytes): self
    {
        return new self(sprintf('APP_KEY must contain at least %d bytes of secret material.', $minimumBytes));
    }
}
