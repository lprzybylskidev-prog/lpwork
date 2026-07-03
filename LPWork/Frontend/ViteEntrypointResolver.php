<?php

declare(strict_types=1);

namespace LPWork\Frontend;

/**
 * Resolves vite entrypoint resolver values into runtime objects.
 */
final readonly class ViteEntrypointResolver
{
    /**
     * Creates a new ViteEntrypointResolver instance.
     */
    public function __construct(
        private AssetEntryRegistry $entries,
    ) {}

    /**
     * @return array<string, ViteEntrypoint>
     */
    public function entries(): array
    {
        $resolved = [];

        foreach ($this->entries->all() as $entry) {
            $viteEntry = ViteEntrypoint::fromAssetEntry($entry);
            $resolved[$viteEntry->name()] = $viteEntry;
        }

        return $resolved;
    }

    /**
     * @return array<string, string>
     */
    public function buildInputs(): array
    {
        $inputs = [];

        foreach ($this->entries() as $entry) {
            $inputs[$entry->buildInputName()] = $entry->sourcePath();
        }

        return $inputs;
    }
}
