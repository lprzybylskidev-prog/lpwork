<?php

declare(strict_types=1);

namespace LPWork\Validation\Exceptions;

use RuntimeException;

/**
 * Reports invalid form request exception failures.
 */
final class InvalidFormRequestException extends RuntimeException
{
    /**
     * Builds or returns resolved object.
     */
    public static function resolvedObject(string $formRequest): self
    {
        return new self(sprintf(
            'Form request [%s] must resolve to a FormRequest instance.',
            $formRequest,
        ));
    }
}
