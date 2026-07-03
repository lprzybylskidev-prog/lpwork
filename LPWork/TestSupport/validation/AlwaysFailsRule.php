<?php

declare(strict_types=1);

namespace Tests\support\validation;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\ValidationMessage;

final readonly class AlwaysFailsRule implements ValidationRule
{
    public function __construct(
        private string $name = 'fail',
        private string $message = 'validation.failed',
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @param array<string, mixed> $input
     * @param array<array-key, mixed> $parameters
     */
    public function validate(string $field, mixed $value, array $input, array $parameters = []): ?ValidationMessage
    {
        if ($this->message === '') {
            return null;
        }

        return new ValidationMessage($this->message, [
            'field' => $field,
            'value' => $value,
            'parameters' => $parameters,
        ]);
    }
}
