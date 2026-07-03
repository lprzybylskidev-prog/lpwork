<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\FileValidationReader;
use LPWork\Validation\ValidationMessage;
use LPWork\Validation\ValidationRuleParameterReader;

/**
 * Represents the mimes rule framework component.
 */
final readonly class MimesRule implements ValidationRule
{
    /**
     * Creates a new MimesRule instance.
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
        return 'mimes';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        $allowed = array_map('strtolower', $this->parameters->strings($parameters, $this->name(), 'mimes'));
        $mime = $this->files->mime($value);
        $extension = $this->files->extension($value);

        foreach ($allowed as $type) {
            if ($mime === $type || $extension === $type || ($extension !== null && str_ends_with($type, '/' . $extension))) {
                return null;
            }
        }

        return new ValidationMessage('validation.mimes', ['field' => $field, 'mimes' => implode(', ', $allowed)]);
    }
}
