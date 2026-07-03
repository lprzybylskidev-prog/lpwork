<?php

declare(strict_types=1);

namespace LPWork\Frontend;

use LPWork\Frontend\Exceptions\ApplicationAssetManifestEntryNotFoundException;

/**
 * Represents the application asset manifest framework component.
 */
final readonly class ApplicationAssetManifest
{
    /**
     * @param array<string, ApplicationAssetManifestEntry> $entries
     */
    public function __construct(
        private array $entries,
    ) {}

    /**
     * Performs the entry operation.
     */
    public function entry(string $sourcePath): ApplicationAssetManifestEntry
    {
        return $this->entries[$sourcePath] ?? throw new ApplicationAssetManifestEntryNotFoundException($sourcePath);
    }
}
