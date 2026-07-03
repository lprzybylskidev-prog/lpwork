<?php

declare(strict_types=1);

namespace LPWork\Console\Completion;

/**
 * Represents the completion argument framework component.
 */
final readonly class CompletionArgument
{
    /**
     * Creates a new CompletionArgument instance.
     */
    public function __construct(
        private string $name,
        private string $description,
        private bool $required,
    ) {}

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
}
