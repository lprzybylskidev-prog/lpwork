<?php

declare(strict_types=1);

namespace LPWork\Console\FileCreators;

/**
 * Registers optional module provider services with the framework container.
 */
final readonly class OptionalModuleProvider
{
    /**
     * Creates a new OptionalModuleProvider instance.
     */
    public function __construct(
        private string $path,
        private string $class,
        private string $contents,
        private ProviderRegistration $moduleRegistration,
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
     * Performs the contents operation.
     */
    public function contents(): string
    {
        return $this->contents;
    }

    /**
     * Performs the module registration operation.
     */
    public function moduleRegistration(): ProviderRegistration
    {
        return $this->moduleRegistration;
    }
}
