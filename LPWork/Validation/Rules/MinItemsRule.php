<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\ValidationMessage;
use LPWork\Validation\ValidationRuleParameterReader;

/**
 * Represents the min items rule framework component.
 */
final readonly class MinItemsRule implements ValidationRule
{
    /**
     * Creates a new MinItemsRule instance.
     */
    public function __construct(private ValidationRuleParameterReader $parameters = new ValidationRuleParameterReader()) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'min_items';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        $minimum = (int) $this->parameters->numeric($parameters, $this->name(), 'min');

        return is_array($value) && count($value) >= $minimum
            ? null
            : new ValidationMessage('validation.min_items', ['field' => $field, 'min' => $minimum]);
    }
}
