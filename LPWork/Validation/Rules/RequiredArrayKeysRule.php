<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\ValidationMessage;
use LPWork\Validation\ValidationRuleParameterReader;

/**
 * Represents the required array keys rule framework component.
 */
final readonly class RequiredArrayKeysRule implements ValidationRule
{
    /**
     * Creates a new RequiredArrayKeysRule instance.
     */
    public function __construct(private ValidationRuleParameterReader $parameters = new ValidationRuleParameterReader()) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'required_array_keys';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        $keys = $this->parameters->strings($parameters, $this->name(), 'keys');

        if (is_array($value)) {
            foreach ($keys as $key) {
                if (!array_key_exists($key, $value)) {
                    return new ValidationMessage('validation.required_array_keys', ['field' => $field, 'keys' => implode(', ', $keys)]);
                }
            }

            return null;
        }

        return new ValidationMessage('validation.required_array_keys', ['field' => $field, 'keys' => implode(', ', $keys)]);
    }
}
