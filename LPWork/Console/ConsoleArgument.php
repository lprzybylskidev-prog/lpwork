<?php

declare(strict_types=1);

namespace LPWork\Console;

/**
 * Represents the console argument framework component.
 */
final readonly class ConsoleArgument
{
    private function __construct(
        private string $name,
        private string $description,
        private bool $required,
    ) {}

    /**
     * Performs the required operation.
     */
    public static function required(string $name, string $description = ''): self
    {
        return new self($name, $description, true);
    }

    /**
     * Performs the optional operation.
     */
    public static function optional(string $name, string $description = ''): self
    {
        return new self($name, $description, false);
    }

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return $this->description;
    }

    /**
     * Reports whether is required.
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * Performs the usage operation.
     */
    public function usage(): string
    {
        return $this->required ? "<{$this->name}>" : "[{$this->name}]";
    }
}
