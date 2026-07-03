<?php

declare(strict_types=1);

namespace Tests\support\validation;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\ValidationMessage;

final readonly class RequiredRule implements ValidationRule
{
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
