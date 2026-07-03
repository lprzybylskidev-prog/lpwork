<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\ValidationMessage;
use LPWork\Validation\ValidationRuleParameterReader;

/**
 * Represents the regex rule framework component.
 */
final readonly class RegexRule implements ValidationRule
{
    /**
     * Creates a new RegexRule instance.
     */
    public function __construct(private ValidationRuleParameterReader $parameters = new ValidationRuleParameterReader()) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'regex';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        $pattern = $this->parameters->string($parameters, $this->name(), 'pattern');

        if (is_string($value) && @preg_match($pattern, $value) === 1) {
            return null;
        }

        return new ValidationMessage('validation.regex', ['field' => $field]);
    }
}
