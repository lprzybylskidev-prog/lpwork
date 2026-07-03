<?php

declare(strict_types=1);

namespace LPWork\Validation;

use Stringable;

/**
 * Represents the validation string value framework component.
 */
final readonly class ValidationStringValue
{
    /**
     * Creates a ValidationStringValue instance from from input.
     */
    public function from(mixed $value): ?string
    {
        if (is_scalar($value) || $value instanceof Stringable) {
            return (string) $value;
        }

        return null;
    }
}
