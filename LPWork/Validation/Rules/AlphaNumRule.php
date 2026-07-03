<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\ValidationMessage;

/**
 * Represents the alpha num rule framework component.
 */
final readonly class AlphaNumRule implements ValidationRule
{
    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'alpha_num';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        if (is_string($value) && preg_match('/^[\pL\pN]+$/u', $value) === 1) {
            return null;
        }

        return new ValidationMessage('validation.alpha_num', ['field' => $field]);
    }
}
