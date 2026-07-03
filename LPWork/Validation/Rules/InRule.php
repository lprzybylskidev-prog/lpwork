<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\ValidationMessage;
use LPWork\Validation\ValidationRuleParameterReader;
use LPWork\Validation\ValidationStringValue;

/**
 * Represents the in rule framework component.
 */
final readonly class InRule implements ValidationRule
{
    /**
     * Creates a new InRule instance.
     */
    public function __construct(
        private ValidationRuleParameterReader $parameters = new ValidationRuleParameterReader(),
        private ValidationStringValue $strings = new ValidationStringValue(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'in';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        $values = $this->parameters->strings($parameters, $this->name(), 'values');
        $string = $this->strings->from($value);

        if ($string !== null && in_array($string, $values, true)) {
            return null;
        }

        return new ValidationMessage('validation.in', [
            'field' => $field,
            'values' => implode(', ', $values),
        ]);
    }
}
