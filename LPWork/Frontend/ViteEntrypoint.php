<?php

declare(strict_types=1);

namespace LPWork\Frontend;

/**
 * Represents the vite entrypoint framework component.
 */
final readonly class ViteEntrypoint
{
    /**
     * Creates a new ViteEntrypoint instance.
     */
    public function __construct(
        private string $name,
        private string $buildInputName,
        private string $sourcePath,
    ) {}

    /**
     * Creates a ViteEntrypoint instance from from asset entry input.
     */
    public static function fromAssetEntry(AssetEntry $entry): self
    {
        return new self(
            name: $entry->name(),
            buildInputName: str_replace('::', '/', $entry->name()),
            sourcePath: $entry->sourcePath(),
        );
    }

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Builds or returns build input name.
     */
    public function buildInputName(): string
    {
        return $this->buildInputName;
    }

    /**
     * Performs the source path operation.
     */
    public function sourcePath(): string
    {
        return $this->sourcePath;
    }
}
