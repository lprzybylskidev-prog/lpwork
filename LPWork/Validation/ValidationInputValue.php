<?php

declare(strict_types=1);

namespace LPWork\Validation;

/**
 * Represents the validation input value framework component.
 */
final readonly class ValidationInputValue
{
    /**
     * @param array<string, mixed> $input
     */
    public function value(array $input, string $field): mixed
    {
        if (array_key_exists($field, $input)) {
            return $input[$field];
        }

        $value = $input;

        foreach (explode('.', $field) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}
