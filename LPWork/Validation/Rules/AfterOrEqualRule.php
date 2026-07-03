<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\ValidationDateParser;
use LPWork\Validation\ValidationInputValue;
use LPWork\Validation\ValidationMessage;
use LPWork\Validation\ValidationRuleParameterReader;

/**
 * Represents the after or equal rule framework component.
 */
final readonly class AfterOrEqualRule implements ValidationRule
{
    /**
     * Creates a new AfterOrEqualRule instance.
     */
    public function __construct(
        private ValidationRuleParameterReader $parameters = new ValidationRuleParameterReader(),
        private ValidationDateParser $dates = new ValidationDateParser(),
        private ValidationInputValue $inputValue = new ValidationInputValue(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'after_or_equal';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        $target = $this->parameters->string($parameters, $this->name(), 'date');
        $date = $this->dates->parse($value);
        $limit = $this->dates->parse($this->inputValue->value($input, $target) ?? $target);

        if ($date !== null && $limit !== null && $date >= $limit) {
            return null;
        }

        return new ValidationMessage('validation.after_or_equal', ['field' => $field, 'date' => $target]);
    }
}
