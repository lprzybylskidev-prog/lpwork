<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\ValidationMessage;
use LPWork\Validation\ValidationRuleParameterReader;

/**
 * Represents the starts with rule framework component.
 */
final readonly class StartsWithRule implements ValidationRule
{
    /**
     * Creates a new StartsWithRule instance.
     */
    public function __construct(private ValidationRuleParameterReader $parameters = new ValidationRuleParameterReader()) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'starts_with';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        $prefixes = $this->parameters->strings($parameters, $this->name(), 'prefixes');

        if (is_string($value)) {
            foreach ($prefixes as $prefix) {
                if (str_starts_with($value, $prefix)) {
                    return null;
                }
            }
        }

        return new ValidationMessage('validation.starts_with', ['field' => $field, 'values' => implode(', ', $prefixes)]);
    }
}
