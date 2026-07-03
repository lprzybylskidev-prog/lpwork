<?php

declare(strict_types=1);

namespace LPWork\Console\FileCreators;

/**
 * Represents the result of file creator result work.
 */
final readonly class FileCreatorResult
{
    /**
     * Creates a new FileCreatorResult instance.
     */
    public function __construct(
        private string $path,
        private string $class,
        private bool $created,
        private ?string $providerPath = null,
        private bool $registered = false,
    ) {}

    /**
     * Returns path.
     */
    public function path(): string
    {
        return $this->path;
    }

    public function class(): string
    {
        return $this->class;
    }

    /**
     * Builds or returns created.
     */
    public function created(): bool
    {
        return $this->created;
    }

    /**
     * Performs the provider path operation.
     */
    public function providerPath(): ?string
    {
        return $this->providerPath;
    }

    /**
     * Registers or stores registered.
     */
    public function registered(): bool
    {
        return $this->registered;
    }
}
