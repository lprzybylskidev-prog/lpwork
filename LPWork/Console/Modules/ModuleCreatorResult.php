<?php

declare(strict_types=1);

namespace LPWork\Console\Modules;

/**
 * Represents the result of module creator result work.
 */
final readonly class ModuleCreatorResult
{
    /**
     * @param list<string> $paths
     */
    public function __construct(
        private string $modulePath,
        private string $serviceProviderClass,
        private array $paths,
        private ?string $registeredProviderPath = null,
    ) {}

    /**
     * Performs the module path operation.
     */
    public function modulePath(): string
    {
        return $this->modulePath;
    }

    /**
     * Performs the service provider class operation.
     */
    public function serviceProviderClass(): string
    {
        return $this->serviceProviderClass;
    }

    /**
     * @return list<string>
     */
    public function paths(): array
    {
        return $this->paths;
    }

    /**
     * Registers or stores registered.
     */
    public function registered(): bool
    {
        return $this->registeredProviderPath !== null;
    }

    /**
     * Registers or stores registered provider path.
     */
    public function registeredProviderPath(): ?string
    {
        return $this->registeredProviderPath;
    }
}
