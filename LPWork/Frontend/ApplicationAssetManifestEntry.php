<?php

declare(strict_types=1);

namespace LPWork\Frontend;

/**
 * Represents the application asset manifest entry framework component.
 */
final readonly class ApplicationAssetManifestEntry
{
    /**
     * @param list<string> $css
     */
    public function __construct(
        private string $file,
        private array $css = [],
    ) {}

    /**
     * Performs the file operation.
     */
    public function file(): string
    {
        return $this->file;
    }

    /**
     * @return list<string>
     */
    public function css(): array
    {
        return $this->css;
    }
}
