<?php

declare(strict_types=1);

namespace LPWork\Console\FileCreators;

/**
 * Represents the file creator definition framework component.
 */
final readonly class FileCreatorDefinition
{
    /**
     * @param array<string, string> $replacements
     */
    public function __construct(
        private string $type,
        private string $description,
        private string $defaultDirectory,
        private string $suffix,
        private string $template,
        private ?ProviderRegistration $registration = null,
        private array $replacements = [],
    ) {}

    /**
     * Performs the type operation.
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * Performs the command name operation.
     */
    public function commandName(): string
    {
        return 'make:' . $this->type;
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return $this->description;
    }

    /**
     * Returns default directory.
     */
    public function defaultDirectory(): string
    {
        return $this->defaultDirectory;
    }

    /**
     * Performs the suffix operation.
     */
    public function suffix(): string
    {
        return $this->suffix;
    }

    /**
     * Performs the template operation.
     */
    public function template(): string
    {
        return $this->template;
    }

    /**
     * Performs the registration operation.
     */
    public function registration(): ?ProviderRegistration
    {
        return $this->registration;
    }

    /**
     * @return array<string, string>
     */
    public function replacements(): array
    {
        return $this->replacements;
    }
}
