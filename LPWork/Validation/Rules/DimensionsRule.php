<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\FileValidationReader;
use LPWork\Validation\ValidationMessage;
use LPWork\Validation\ValidationRuleParameterReader;

/**
 * Represents the dimensions rule framework component.
 */
final readonly class DimensionsRule implements ValidationRule
{
    /**
     * Creates a new DimensionsRule instance.
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
        return 'dimensions';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        $width = (int) $this->parameters->numericAt($parameters, 0, $this->name(), 'width');
        $height = (int) $this->parameters->numericAt($parameters, 1, $this->name(), 'height');

        return $this->files->dimensions($value) === [$width, $height]
            ? null
            : new ValidationMessage('validation.dimensions', ['field' => $field, 'width' => $width, 'height' => $height]);
    }
}
