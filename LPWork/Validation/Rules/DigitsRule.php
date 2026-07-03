<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\ValidationMessage;
use LPWork\Validation\ValidationRuleParameterReader;

/**
 * Represents the digits rule framework component.
 */
final readonly class DigitsRule implements ValidationRule
{
    /**
     * Creates a new DigitsRule instance.
     */
    public function __construct(private ValidationRuleParameterReader $parameters = new ValidationRuleParameterReader()) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'digits';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        $digits = (int) $this->parameters->numeric($parameters, $this->name(), 'digits');
        $string = is_int($value) || is_string($value) ? (string) $value : '';

        return preg_match('/^\d+$/', $string) === 1 && strlen($string) === $digits
            ? null
            : new ValidationMessage('validation.digits', ['field' => $field, 'digits' => $digits]);
    }
}
