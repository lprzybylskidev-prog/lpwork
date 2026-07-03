<?php

declare(strict_types=1);

namespace LPWork\Validation;

/**
 * Represents the validation value size framework component.
 */
final readonly class ValidationValueSize
{
    /**
     * Performs the measure operation.
     */
    public function measure(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            return (float) strlen($value);
        }

        if (is_array($value)) {
            return (float) count($value);
        }

        return 0.0;
    }
}
