<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\FileValidationReader;
use LPWork\Validation\ValidationMessage;
use LPWork\Validation\ValidationRuleParameterReader;

/**
 * Represents the file size rule framework component.
 */
final readonly class FileSizeRule implements ValidationRule
{
    /**
     * Creates a new FileSizeRule instance.
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
        return 'file_size';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        $expected = (int) $this->parameters->numeric($parameters, $this->name(), 'bytes');

        return $this->files->size($value) === $expected
            ? null
            : new ValidationMessage('validation.file_size', ['field' => $field, 'size' => $expected]);
    }
}
