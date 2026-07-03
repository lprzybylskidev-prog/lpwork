<?php

declare(strict_types=1);

namespace LPWork\Validation;

use LPWork\Validation\Exceptions\ValidationException;

/**
 * Represents the result of validation result work.
 */
final readonly class ValidationResult
{
    /**
     * @param array<string, mixed> $validated
     */
    public function __construct(
        private array $validated,
        private ValidationErrorBag $errors,
    ) {}

    /**
     * Performs the passes operation.
     */
    public function passes(): bool
    {
        return $this->errors->isEmpty();
    }

    /**
     * Performs the fails operation.
     */
    public function fails(): bool
    {
        return !$this->passes();
    }

    /**
     * @return array<string, mixed>
     */
    public function validated(): array
    {
        return $this->passes() ? $this->validated : [];
    }

    /**
     * Performs the errors operation.
     */
    public function errors(): ValidationErrorBag
    {
        return $this->errors;
    }

    public function throw(): self
    {
        if ($this->fails()) {
            throw new ValidationException($this->errors);
        }

        return $this;
    }
}
