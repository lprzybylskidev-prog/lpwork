<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\ValidationMessage;

/**
 * Represents the numeric rule framework component.
 */
final readonly class NumericRule implements ValidationRule
{
    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'numeric';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
            return null;
        }

        return new ValidationMessage('validation.numeric', [
            'field' => $field,
        ]);
    }
}
