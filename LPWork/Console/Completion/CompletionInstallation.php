<?php

declare(strict_types=1);

namespace LPWork\Console\Completion;

/**
 * Represents the completion installation framework component.
 */
final readonly class CompletionInstallation
{
    /**
     * Creates a new CompletionInstallation instance.
     */
    public function __construct(
        private string $shell,
        private string $file,
        private string $activationCommand,
    ) {}

    /**
     * Performs the shell operation.
     */
    public function shell(): string
    {
        return $this->shell;
    }

    /**
     * Performs the file operation.
     */
    public function file(): string
    {
        return $this->file;
    }

    /**
     * Performs the activation command operation.
     */
    public function activationCommand(): string
    {
        return $this->activationCommand;
    }
}
