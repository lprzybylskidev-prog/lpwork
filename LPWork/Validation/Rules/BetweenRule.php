<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\ValidationMessage;
use LPWork\Validation\ValidationRuleParameterReader;
use LPWork\Validation\ValidationValueSize;

/**
 * Represents the between rule framework component.
 */
final readonly class BetweenRule implements ValidationRule
{
    /**
     * Creates a new BetweenRule instance.
     */
    public function __construct(
        private ValidationRuleParameterReader $parameters = new ValidationRuleParameterReader(),
        private ValidationValueSize $size = new ValidationValueSize(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'between';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        $minimum = $this->parameters->numericAt($parameters, 0, $this->name(), 'min');
        $maximum = $this->parameters->numericAt($parameters, 1, $this->name(), 'max');
        $size = $this->size->measure($value);

        if ($size >= $minimum && $size <= $maximum) {
            return null;
        }

        return new ValidationMessage('validation.between', [
            'field' => $field,
            'min' => $minimum,
            'max' => $maximum,
        ]);
    }
}
