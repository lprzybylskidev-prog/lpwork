<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\ValidationMessage;

/**
 * Represents the url rule framework component.
 */
final readonly class UrlRule implements ValidationRule
{
    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'url';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL) !== false) {
            return null;
        }

        return new ValidationMessage('validation.url', ['field' => $field]);
    }
}
