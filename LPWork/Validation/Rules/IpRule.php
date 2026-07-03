<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\ValidationMessage;

/**
 * Represents the ip rule framework component.
 */
final readonly class IpRule implements ValidationRule
{
    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'ip';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        if (is_string($value) && filter_var($value, FILTER_VALIDATE_IP) !== false) {
            return null;
        }

        return new ValidationMessage('validation.ip', ['field' => $field]);
    }
}
