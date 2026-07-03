<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\ValidationMessage;
use LPWork\Validation\ValidationRuleParameterReader;

/**
 * Represents the decimal rule framework component.
 */
final readonly class DecimalRule implements ValidationRule
{
    /**
     * Creates a new DecimalRule instance.
     */
    public function __construct(private ValidationRuleParameterReader $parameters = new ValidationRuleParameterReader()) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'decimal';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        $minimum = (int) $this->parameters->numericAt($parameters, 0, $this->name(), 'min');
        $maximum = $this->parameters->has($parameters, 1)
            ? (int) $this->parameters->numericAt($parameters, 1, $this->name(), 'max')
            : $minimum;
        $string = is_int($value) || is_float($value) || is_string($value) ? (string) $value : '';

        if (preg_match('/^-?\d+\.(\d+)$/', $string, $matches) === 1) {
            $places = strlen($matches[1]);

            if ($places >= $minimum && $places <= $maximum) {
                return null;
            }
        }

        return new ValidationMessage('validation.decimal', ['field' => $field, 'min' => $minimum, 'max' => $maximum]);
    }
}
