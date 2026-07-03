<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\ValidationMessage;
use LPWork\Validation\ValidationRuleParameterReader;
use LPWork\Validation\ValidationValueSize;

/**
 * Represents the min rule framework component.
 */
final readonly class MinRule implements ValidationRule
{
    /**
     * Creates a new MinRule instance.
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
        return 'min';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        $minimum = $this->parameters->numeric($parameters, $this->name(), 'min');

        if ($this->size->measure($value) >= $minimum) {
            return null;
        }

        return new ValidationMessage('validation.min', [
            'field' => $field,
            'min' => $minimum,
        ]);
    }
}
