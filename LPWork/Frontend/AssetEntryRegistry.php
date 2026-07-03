<?php

declare(strict_types=1);

namespace LPWork\Frontend;

use LPWork\Frontend\Exceptions\InvalidAssetEntryDeclarationException;

/**
 * Stores and resolves asset entry registry registrations.
 */
final class AssetEntryRegistry
{
    /**
     * @var array<string, AssetEntry>
     */
    private array $entries = [];

    /**
     * Adds an item to this component's registry or backing store.
     */
    public function add(string $name, string $sourcePath): void
    {
        $name = trim($name);
        $sourcePath = trim($sourcePath);

        $this->assertValidName($name);
        $this->assertValidSourcePath($name, $sourcePath);

        if (isset($this->entries[$name])) {
            throw InvalidAssetEntryDeclarationException::duplicate($name);
        }

        $this->entries[$name] = new AssetEntry($name, $sourcePath);
    }

    /**
     * Reports whether has.
     */
    public function has(string $name): bool
    {
        return isset($this->entries[$name]);
    }

    /**
     * Returns the requested value from this component.
     */
    public function get(string $name): ?AssetEntry
    {
        return $this->entries[$name] ?? null;
    }

    /**
     * @return array<string, AssetEntry>
     */
    public function all(): array
    {
        return $this->entries;
    }

    private function assertValidName(string $name): void
    {
        if (preg_match('/^[a-z][a-z0-9_-]*::[a-z][a-z0-9_-]*$/', $name) === 1) {
            return;
        }

        throw InvalidAssetEntryDeclarationException::invalidName($name);
    }

    private function assertValidSourcePath(string $name, string $sourcePath): void
    {
        if ($sourcePath === '' || str_starts_with($sourcePath, '/') || str_contains($sourcePath, '\\')) {
            throw InvalidAssetEntryDeclarationException::invalidSourcePath($name, $sourcePath);
        }

        foreach (explode('/', $sourcePath) as $segment) {
            if ($segment === '..') {
                throw InvalidAssetEntryDeclarationException::invalidSourcePath($name, $sourcePath);
            }
        }
    }
}
