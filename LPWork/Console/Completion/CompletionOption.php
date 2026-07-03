<?php

declare(strict_types=1);

namespace LPWork\Console\Completion;

/**
 * Represents the completion option framework component.
 */
final readonly class CompletionOption
{
    /**
     * Creates a new CompletionOption instance.
     */
    public function __construct(
        private string $name,
        private ?string $shortcut,
        private string $description,
    ) {}

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
     * Performs the long name operation.
     */
    public function longName(): string
    {
        return '--' . $this->name;
    }

    /**
     * Performs the short name operation.
     */
    public function shortName(): ?string
    {
        return $this->shortcut === null ? null : '-' . $this->shortcut;
    }
}
