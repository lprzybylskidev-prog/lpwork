<?php

declare(strict_types=1);

namespace LPWork\Validation;

/**
 * Represents the validation message framework component.
 */
final readonly class ValidationMessage
{
    /**
     * @param array<array-key, mixed> $parameters
     */
    public function __construct(
        private string $key,
        private array $parameters = [],
    ) {}

    /**
     * Returns the stable key used to identify this object.
     */
    public function key(): string
    {
        return $this->key;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function parameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return array{key: string, parameters: array<array-key, mixed>}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'parameters' => $this->parameters,
        ];
    }
}
