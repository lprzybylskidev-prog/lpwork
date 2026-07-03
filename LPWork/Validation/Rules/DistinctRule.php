<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\ValidationMessage;

/**
 * Represents the distinct rule framework component.
 */
final readonly class DistinctRule implements ValidationRule
{
    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'distinct';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        if (!is_array($value)) {
            return new ValidationMessage('validation.distinct', ['field' => $field]);
        }

        $seen = [];

        foreach ($value as $item) {
            $key = is_scalar($item) || $item === null ? get_debug_type($item) . ':' . (string) $item : serialize($item);

            if (isset($seen[$key])) {
                return new ValidationMessage('validation.distinct', ['field' => $field]);
            }

            $seen[$key] = true;
        }

        return null;
    }
}
