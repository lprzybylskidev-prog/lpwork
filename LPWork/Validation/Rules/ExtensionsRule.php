<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\FileValidationReader;
use LPWork\Validation\ValidationMessage;
use LPWork\Validation\ValidationRuleParameterReader;

/**
 * Represents the extensions rule framework component.
 */
final readonly class ExtensionsRule implements ValidationRule
{
    /**
     * Creates a new ExtensionsRule instance.
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
        return 'extensions';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        $allowed = array_map('strtolower', $this->parameters->strings($parameters, $this->name(), 'extensions'));
        $extension = $this->files->extension($value);

        return $extension !== null && in_array($extension, $allowed, true)
            ? null
            : new ValidationMessage('validation.extensions', ['field' => $field, 'extensions' => implode(', ', $allowed)]);
    }
}
