<?php

declare(strict_types=1);

namespace LPWork\Console\Completion;

/**
 * Handles the completion command console command.
 */
final readonly class CompletionCommand
{
    /**
     * @param list<CompletionArgument> $arguments
     * @param list<CompletionOption> $options
     */
    public function __construct(
        private string $name,
        private string $description,
        private array $arguments,
        private array $options,
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
     * @return list<CompletionArgument>
     */
    public function arguments(): array
    {
        return $this->arguments;
    }

    /**
     * @return list<CompletionOption>
     */
    public function options(): array
    {
        return $this->options;
    }
}
