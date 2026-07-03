<?php

declare(strict_types=1);

namespace LPWork\Validation\Contracts;

use LPWork\Validation\ValidationMessage;

/**
 * Defines the contract for validation rule.
 */
interface ValidationRule
{
    /**
     * Returns the configured name for this object.
     */
    public function name(): string;

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage;
}
