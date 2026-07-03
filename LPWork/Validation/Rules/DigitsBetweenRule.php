<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\ValidationMessage;
use LPWork\Validation\ValidationRuleParameterReader;

/**
 * Represents the digits between rule framework component.
 */
final readonly class DigitsBetweenRule implements ValidationRule
{
    /**
     * Creates a new DigitsBetweenRule instance.
     */
    public function __construct(private ValidationRuleParameterReader $parameters = new ValidationRuleParameterReader()) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'digits_between';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        $minimum = (int) $this->parameters->numericAt($parameters, 0, $this->name(), 'min');
        $maximum = (int) $this->parameters->numericAt($parameters, 1, $this->name(), 'max');
        $string = is_int($value) || is_string($value) ? (string) $value : '';
        $length = strlen($string);

        return preg_match('/^\d+$/', $string) === 1 && $length >= $minimum && $length <= $maximum
            ? null
            : new ValidationMessage('validation.digits_between', ['field' => $field, 'min' => $minimum, 'max' => $maximum]);
    }
}
