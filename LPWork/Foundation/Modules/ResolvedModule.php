<?php

declare(strict_types=1);

namespace LPWork\Foundation\Modules;

/**
 * Represents the resolved module framework component.
 */
final readonly class ResolvedModule
{
    /**
     * Creates a new ResolvedModule instance.
     */
    public function __construct(
        private string $name,
        private string $path,
        private string $namespace,
        private string $serviceProviderClass,
        private string $serviceProviderPath,
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Returns path.
     */
    public function path(string $path = ''): string
    {
        if ($path === '') {
            return $this->path;
        }

        return $this->path . '/' . ltrim($path, '/');
    }

    public function namespace(string $namespace = ''): string
    {
        if ($namespace === '') {
            return $this->namespace;
        }

        return $this->namespace . '\\' . trim($namespace, '\\');
    }

    /**
     * Performs the service provider class operation.
     */
    public function serviceProviderClass(): string
    {
        return $this->serviceProviderClass;
    }

    /**
     * Performs the service provider path operation.
     */
    public function serviceProviderPath(): string
    {
        return $this->serviceProviderPath;
    }

    /**
     * Performs the child provider class operation.
     */
    public function childProviderClass(string $namespace, string $provider): string
    {
        return $this->namespace($namespace) . '\\' . $provider;
    }

    /**
     * Performs the child provider path operation.
     */
    public function childProviderPath(string $path, string $provider): string
    {
        return $this->path($path . '/' . $provider . '.php');
    }
}
