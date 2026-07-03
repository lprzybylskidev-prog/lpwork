<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\ValidationInputValue;
use LPWork\Validation\ValidationMessage;

/**
 * Represents the confirmed rule framework component.
 */
final readonly class ConfirmedRule implements ValidationRule
{
    /**
     * Creates a new ConfirmedRule instance.
     */
    public function __construct(
        private ValidationInputValue $inputValue = new ValidationInputValue(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'confirmed';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        if ($value === $this->inputValue->value($input, $field . '_confirmation')) {
            return null;
        }

        return new ValidationMessage('validation.confirmed', [
            'field' => $field,
        ]);
    }
}
