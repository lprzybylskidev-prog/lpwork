<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\FileValidationReader;
use LPWork\Validation\ValidationMessage;

/**
 * Represents the image rule framework component.
 */
final readonly class ImageRule implements ValidationRule
{
    /**
     * Creates a new ImageRule instance.
     */
    public function __construct(private FileValidationReader $files = new FileValidationReader()) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'image';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        $mime = $this->files->mime($value);

        return $this->files->isFile($value) && is_string($mime) && str_starts_with($mime, 'image/')
            ? null
            : new ValidationMessage('validation.image', ['field' => $field]);
    }
}
