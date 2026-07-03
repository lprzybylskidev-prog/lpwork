<?php

declare(strict_types=1);

namespace LPWork\Config;

use LPWork\Config\Enums\EnvironmentRequirementType;

/**
 * Represents the environment requirement framework component.
 */
final readonly class EnvironmentRequirement
{
    private function __construct(
        public string $key,
        public EnvironmentRequirementType $type,
        public bool $allowEmpty = true,
        public ?string $conditionKey = null,
        public ?string $conditionValue = null,
    ) {}

    /**
     * Performs the string operation.
     */
    public static function string(string $key): self
    {
        return new self($key, EnvironmentRequirementType::String);
    }

    /**
     * Performs the non empty string operation.
     */
    public static function nonEmptyString(string $key): self
    {
        return new self($key, EnvironmentRequirementType::String, allowEmpty: false);
    }

    /**
     * Performs the int operation.
     */
    public static function int(string $key): self
    {
        return new self($key, EnvironmentRequirementType::Integer);
    }

    /**
     * Performs the float operation.
     */
    public static function float(string $key): self
    {
        return new self($key, EnvironmentRequirementType::Float);
    }

    /**
     * Performs the bool operation.
     */
    public static function bool(string $key): self
    {
        return new self($key, EnvironmentRequirementType::Boolean);
    }

    /**
     * Performs the when operation.
     */
    public function when(string $key, string $value): self
    {
        return new self($this->key, $this->type, $this->allowEmpty, $key, $value);
    }

    /**
     * Performs the identity operation.
     */
    public function identity(): string
    {
        return implode('|', [
            $this->key,
            $this->type->value,
            $this->allowEmpty ? 'empty' : 'non-empty',
            $this->conditionKey ?? '',
            $this->conditionValue ?? '',
        ]);
    }
}
