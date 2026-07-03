<?php

declare(strict_types=1);

namespace LPWork\Validation\Rules;

use const FILTER_FLAG_IPV4;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\ValidationMessage;

/**
 * Represents the ipv4 rule framework component.
 */
final readonly class Ipv4Rule implements ValidationRule
{
    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'ipv4';
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        if (is_string($value) && filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return null;
        }

        return new ValidationMessage('validation.ipv4', ['field' => $field]);
    }
}
