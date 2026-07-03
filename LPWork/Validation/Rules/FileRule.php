<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\FileValidationReader;
use LPWork\Validation\ValidationMessage;

/**
 * Represents the file rule framework component.
 */
final readonly class FileRule implements ValidationRule
{
    /**
     * Creates a new FileRule instance.
     */
    public function __construct(private FileValidationReader $files = new FileValidationReader()) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'file';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        return $this->files->isFile($value)
            ? null
            : new ValidationMessage('validation.file', ['field' => $field]);
    }
}
