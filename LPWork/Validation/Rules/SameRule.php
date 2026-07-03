<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\ValidationInputValue;
use LPWork\Validation\ValidationMessage;
use LPWork\Validation\ValidationRuleParameterReader;

/**
 * Represents the same rule framework component.
 */
final readonly class SameRule implements ValidationRule
{
    /**
     * Creates a new SameRule instance.
     */
    public function __construct(
        private ValidationRuleParameterReader $parameters = new ValidationRuleParameterReader(),
        private ValidationInputValue $inputValue = new ValidationInputValue(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'same';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        $other = $this->parameters->string($parameters, $this->name(), 'field');

        if ($value === $this->inputValue->value($input, $other)) {
            return null;
        }

        return new ValidationMessage('validation.same', [
            'field' => $field,
            'other' => $other,
        ]);
    }
}
