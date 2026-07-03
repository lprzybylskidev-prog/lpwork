<?php

declare(strict_types=1);

namespace LPWork\Validation;

/**
 * Represents the validation numeric value framework component.
 */
final readonly class ValidationNumericValue
{
    /**
     * Performs the number operation.
     */
    public function number(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (float) $value;
        }

        return null;
    }
}
