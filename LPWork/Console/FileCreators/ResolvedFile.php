<?php

declare(strict_types=1);

namespace LPWork\Console\FileCreators;

/**
 * Represents the resolved file framework component.
 */
final readonly class ResolvedFile
{
    /**
     * Creates a new ResolvedFile instance.
     */
    public function __construct(
        private string $path,
        private string $namespace,
        private string $className,
        private string $class,
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
     * Performs the class name operation.
     */
    public function className(): string
    {
        return $this->className;
    }

    public function class(): string
    {
        return $this->class;
    }
}
