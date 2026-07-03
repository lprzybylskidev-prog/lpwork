<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\ValidationMessage;
use LPWork\Validation\ValidationRuleParameterReader;

/**
 * Represents the count rule framework component.
 */
final readonly class CountRule implements ValidationRule
{
    /**
     * Creates a new CountRule instance.
     */
    public function __construct(private ValidationRuleParameterReader $parameters = new ValidationRuleParameterReader()) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'count';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        $count = (int) $this->parameters->numeric($parameters, $this->name(), 'count');

        return is_array($value) && count($value) === $count
            ? null
            : new ValidationMessage('validation.count', ['field' => $field, 'count' => $count]);
    }
}
