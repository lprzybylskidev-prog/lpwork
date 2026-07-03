<?php

declare(strict_types=1);

namespace LPWork\Frontend;

/**
 * Represents the asset entry framework component.
 */
final readonly class AssetEntry
{
    /**
     * Creates a new AssetEntry instance.
     */
    public function __construct(
        private string $name,
        private string $sourcePath,
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Performs the source path operation.
     */
    public function sourcePath(): string
    {
        return $this->sourcePath;
    }
}
