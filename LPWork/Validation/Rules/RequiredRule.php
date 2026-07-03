<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\ValidationMessage;

/**
 * Represents the required rule framework component.
 */
final readonly class RequiredRule implements ValidationRule
{
    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'required';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        if ($value !== null && $value !== '' && $value !== []) {
            return null;
        }

        return new ValidationMessage('validation.required', [
            'field' => $field,
        ]);
    }
}
