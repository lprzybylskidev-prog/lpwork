<?php

declare(strict_types=1);

namespace LPWork\Validation;

/**
 * Represents the validation error framework component.
 */
final readonly class ValidationError
{
    /**
     * Creates a new ValidationError instance.
     */
    public function __construct(
        private string $field,
        private ValidationMessage $message,
    ) {}

    /**
     * Performs the field operation.
     */
    public function field(): string
    {
        return $this->field;
    }

    /**
     * Performs the message operation.
     */
    public function message(): ValidationMessage
    {
        return $this->message;
    }

    /**
     * @return array{field: string, message: array{key: string, parameters: array<array-key, mixed>}}
     */
    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'message' => $this->message->toArray(),
        ];
    }
}
