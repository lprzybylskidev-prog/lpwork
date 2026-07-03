<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\ValidationMessage;
use LPWork\Validation\ValidationRuleParameterReader;
use LPWork\Validation\ValidationValueSize;

/**
 * Represents the max rule framework component.
 */
final readonly class MaxRule implements ValidationRule
{
    /**
     * Creates a new MaxRule instance.
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
        return 'max';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        $maximum = $this->parameters->numeric($parameters, $this->name(), 'max');

        if ($this->size->measure($value) <= $maximum) {
            return null;
        }

        return new ValidationMessage('validation.max', [
            'field' => $field,
            'max' => $maximum,
        ]);
    }
}
