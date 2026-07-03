<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\ValidationMessage;
use LPWork\Validation\ValidationRuleParameterReader;
use LPWork\Validation\ValidationValueSize;

/**
 * Represents the size rule framework component.
 */
final readonly class SizeRule implements ValidationRule
{
    /**
     * Creates a new SizeRule instance.
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
        return 'size';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        $expected = $this->parameters->numeric($parameters, $this->name(), 'size');

        if ($this->size->measure($value) === $expected) {
            return null;
        }

        return new ValidationMessage('validation.size', [
            'field' => $field,
            'size' => $expected,
        ]);
    }
}
