<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\ValidationDateParser;
use LPWork\Validation\ValidationMessage;

/**
 * Represents the date rule framework component.
 */
final readonly class DateRule implements ValidationRule
{
    /**
     * Creates a new DateRule instance.
     */
    public function __construct(private ValidationDateParser $dates = new ValidationDateParser()) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'date';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        return $this->dates->parse($value) === null
            ? new ValidationMessage('validation.date', ['field' => $field])
            : null;
    }
}
