<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\ValidationMessage;
use LPWork\Validation\ValidationNumericValue;
use LPWork\Validation\ValidationRuleParameterReader;

/**
 * Represents the gte rule framework component.
 */
final readonly class GteRule implements ValidationRule
{
    /**
     * Creates a new GteRule instance.
     */
    public function __construct(
        private ValidationRuleParameterReader $parameters = new ValidationRuleParameterReader(),
        private ValidationNumericValue $numbers = new ValidationNumericValue(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'gte';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        $minimum = $this->parameters->numeric($parameters, $this->name(), 'value');
        $number = $this->numbers->number($value);

        return $number !== null && $number >= $minimum
            ? null
            : new ValidationMessage('validation.gte', ['field' => $field, 'value' => $minimum]);
    }
}
