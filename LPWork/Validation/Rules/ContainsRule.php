<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\ValidationMessage;
use LPWork\Validation\ValidationRuleParameterReader;

/**
 * Represents the contains rule framework component.
 */
final readonly class ContainsRule implements ValidationRule
{
    /**
     * Creates a new ContainsRule instance.
     */
    public function __construct(private ValidationRuleParameterReader $parameters = new ValidationRuleParameterReader()) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'contains';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        $needles = $this->parameters->strings($parameters, $this->name(), 'values');

        if (is_string($value)) {
            foreach ($needles as $needle) {
                if (str_contains($value, $needle)) {
                    return null;
                }
            }
        }

        return new ValidationMessage('validation.contains', ['field' => $field, 'values' => implode(', ', $needles)]);
    }
}
