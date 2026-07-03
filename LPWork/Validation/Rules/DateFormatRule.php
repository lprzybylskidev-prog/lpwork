<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\ValidationDateParser;
use LPWork\Validation\ValidationMessage;
use LPWork\Validation\ValidationRuleParameterReader;

/**
 * Represents the date format rule framework component.
 */
final readonly class DateFormatRule implements ValidationRule
{
    /**
     * Creates a new DateFormatRule instance.
     */
    public function __construct(
        private ValidationRuleParameterReader $parameters = new ValidationRuleParameterReader(),
        private ValidationDateParser $dates = new ValidationDateParser(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'date_format';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        $format = $this->parameters->string($parameters, $this->name(), 'format');

        return $this->dates->parseFormat($value, $format) === null
            ? new ValidationMessage('validation.date_format', ['field' => $field, 'format' => $format])
            : null;
    }
}
