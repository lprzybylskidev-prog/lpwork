<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use function json_decode;

use const JSON_ERROR_NONE;

use function json_last_error;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\ValidationMessage;

/**
 * Represents the json rule framework component.
 */
final readonly class JsonRule implements ValidationRule
{
    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'json';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        if (is_string($value)) {
            json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return null;
            }
        }

        return new ValidationMessage('validation.json', ['field' => $field]);
    }
}
