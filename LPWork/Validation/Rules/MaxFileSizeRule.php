<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\FileValidationReader;
use LPWork\Validation\ValidationMessage;
use LPWork\Validation\ValidationRuleParameterReader;

/**
 * Represents the max file size rule framework component.
 */
final readonly class MaxFileSizeRule implements ValidationRule
{
    /**
     * Creates a new MaxFileSizeRule instance.
     */
    public function __construct(
        private ValidationRuleParameterReader $parameters = new ValidationRuleParameterReader(),
        private FileValidationReader $files = new FileValidationReader(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'max_file_size';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        $maximum = (int) $this->parameters->numeric($parameters, $this->name(), 'bytes');
        $size = $this->files->size($value);

        return $size !== null && $size <= $maximum
            ? null
            : new ValidationMessage('validation.max_file_size', ['field' => $field, 'max' => $maximum]);
    }
}
