<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\ValidationMessage;
use LPWork\Validation\ValidationRuleParameterReader;

/**
 * Represents the ends with rule framework component.
 */
final readonly class EndsWithRule implements ValidationRule
{
    /**
     * Creates a new EndsWithRule instance.
     */
    public function __construct(private ValidationRuleParameterReader $parameters = new ValidationRuleParameterReader()) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'ends_with';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        $suffixes = $this->parameters->strings($parameters, $this->name(), 'suffixes');

        if (is_string($value)) {
            foreach ($suffixes as $suffix) {
                if (str_ends_with($value, $suffix)) {
                    return null;
                }
            }
        }

        return new ValidationMessage('validation.ends_with', ['field' => $field, 'values' => implode(', ', $suffixes)]);
    }
}
