<?php

declare(strict_types=1);

namespace LPWork\Validation;

/**
 * Represents the validation error bag framework component.
 */
final class ValidationErrorBag
{
    /**
     * @var array<string, list<ValidationError>>
     */
    private array $errors = [];

    /**
     * Adds an item to this component's registry or backing store.
     */
    public function add(string $field, ValidationMessage $message): void
    {
        $this->errors[$field][] = new ValidationError($field, $message);
    }

    /**
     * Reports whether is empty.
     */
    public function isEmpty(): bool
    {
        return $this->errors === [];
    }

    /**
     * Reports whether has.
     */
    public function has(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    /**
     * @return list<ValidationError>
     */
    public function get(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * @return array<string, list<ValidationError>>
     */
    public function all(): array
    {
        return $this->errors;
    }

    /**
     * @return array<string, list<array{field: string, message: array{key: string, parameters: array<array-key, mixed>}}>>
     */
    public function toArray(): array
    {
        $errors = [];

        foreach ($this->errors as $field => $fieldErrors) {
            $errors[$field] = array_map(
                static fn(ValidationError $error): array => $error->toArray(),
                $fieldErrors,
            );
        }

        return $errors;
    }
}
