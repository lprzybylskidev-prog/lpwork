<?php

declare(strict_types=1);

namespace LPWork\Console;

/**
 * Represents the console option framework component.
 */
final readonly class ConsoleOption
{
    private function __construct(
        private string $name,
        private ?string $shortcut,
        private string $description,
        private bool $acceptsValue,
        private bool $multiple,
    ) {}

    /**
     * Performs the flag operation.
     */
    public static function flag(string $name, ?string $shortcut = null, string $description = ''): self
    {
        return new self($name, $shortcut, $description, false, false);
    }

    /**
     * Returns value.
     */
    public static function value(string $name, ?string $shortcut = null, string $description = ''): self
    {
        return new self($name, $shortcut, $description, true, false);
    }

    /**
     * Performs the multiple operation.
     */
    public static function multiple(string $name, ?string $shortcut = null, string $description = ''): self
    {
        return new self($name, $shortcut, $description, true, true);
    }

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Performs the shortcut operation.
     */
    public function shortcut(): ?string
    {
        return $this->shortcut;
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return $this->description;
    }

    /**
     * Performs the accepts value operation.
     */
    public function acceptsValue(): bool
    {
        return $this->acceptsValue;
    }

    /**
     * Reports whether is multiple.
     */
    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    /**
     * Performs signature.
     */
    public function signature(): string
    {
        $signature = "--{$this->name}";

        if ($this->acceptsValue) {
            $signature .= $this->multiple ? '=VALUE...' : '=VALUE';
        }

        if ($this->shortcut === null) {
            return $signature;
        }

        return "-{$this->shortcut}, {$signature}";
    }
}
