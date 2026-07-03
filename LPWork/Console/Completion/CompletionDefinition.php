<?php

declare(strict_types=1);

namespace LPWork\Console\Completion;

/**
 * @param list<CompletionCommand> $commands
 */
final readonly class CompletionDefinition
{
    /**
     * @param list<CompletionCommand> $commands
     */
    public function __construct(
        private string $program,
        private array $commands,
    ) {}

    /**
     * Performs the program operation.
     */
    public function program(): string
    {
        return $this->program;
    }

    /**
     * @return list<CompletionCommand>
     */
    public function commands(): array
    {
        return $this->commands;
    }
}
