<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\ValidationMessage;
use LPWork\Validation\ValidationRuleParameterReader;

/**
 * Represents the max items rule framework component.
 */
final readonly class MaxItemsRule implements ValidationRule
{
    /**
     * Creates a new MaxItemsRule instance.
     */
    public function __construct(private ValidationRuleParameterReader $parameters = new ValidationRuleParameterReader()) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'max_items';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        $maximum = (int) $this->parameters->numeric($parameters, $this->name(), 'max');

        return is_array($value) && count($value) <= $maximum
            ? null
            : new ValidationMessage('validation.max_items', ['field' => $field, 'max' => $maximum]);
    }
}
