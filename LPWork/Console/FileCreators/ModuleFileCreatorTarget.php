<?php

declare(strict_types=1);

namespace LPWork\Console\FileCreators;

/**
 * Represents the module file creator target framework component.
 */
final readonly class ModuleFileCreatorTarget
{
    /**
     * Creates a new ModuleFileCreatorTarget instance.
     */
    public function __construct(
        private string $path,
        private string $namespace,
        private ?ProviderRegistration $registration,
        private ?OptionalModuleProvider $optionalProvider = null,
    ) {}

    /**
     * Returns path.
     */
    public function path(): string
    {
        return $this->path;
    }

    public function namespace(): string
    {
        return $this->namespace;
    }

    /**
     * Performs the registration operation.
     */
    public function registration(): ?ProviderRegistration
    {
        return $this->registration;
    }

    /**
     * Performs the optional provider operation.
     */
    public function optionalProvider(): ?OptionalModuleProvider
    {
        return $this->optionalProvider;
    }
}
