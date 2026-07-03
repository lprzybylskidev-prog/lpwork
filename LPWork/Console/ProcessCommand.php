<?php

declare(strict_types=1);

namespace LPWork\Console;

/**
 * Handles the process command console command.
 */
final readonly class ProcessCommand
{
    /**
     * @param non-empty-list<string> $command
     * @param array<string, string>|null $environment
     */
    public function __construct(
        private array $command,
        private string $workingDirectory,
        private ?array $environment = null,
    ) {}

    /**
     * @return non-empty-list<string>
     */
    public function command(): array
    {
        return $this->command;
    }

    /**
     * Runs working directory.
     */
    public function workingDirectory(): string
    {
        return $this->workingDirectory;
    }

    /**
     * @return array<string, string>|null
     */
    public function environment(): ?array
    {
        return $this->environment;
    }
}
