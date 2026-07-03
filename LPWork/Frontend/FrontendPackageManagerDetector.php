<?php

declare(strict_types=1);

namespace LPWork\Frontend;

use LPWork\Filesystem\Filesystem;

use function rtrim;

/**
 * Represents the frontend package manager detector framework component.
 */
final readonly class FrontendPackageManagerDetector
{
    /**
     * Creates a new FrontendPackageManagerDetector instance.
     */
    public function __construct(
        private string $basePath,
        private Filesystem $files = new Filesystem(),
    ) {}

    /**
     * Performs the detect operation.
     */
    public function detect(): FrontendPackageManager
    {
        foreach ($this->lockfiles() as $candidate) {
            if ($this->files->isFile($this->path($candidate['lockfile']))) {
                return $candidate['manager'];
            }
        }

        return FrontendPackageManager::Npm;
    }

    /**
     * @return list<array{lockfile: string, manager: FrontendPackageManager}>
     */
    private function lockfiles(): array
    {
        return [
            ['lockfile' => 'pnpm-lock.yaml', 'manager' => FrontendPackageManager::Pnpm],
            ['lockfile' => 'yarn.lock', 'manager' => FrontendPackageManager::Yarn],
            ['lockfile' => 'bun.lockb', 'manager' => FrontendPackageManager::Bun],
            ['lockfile' => 'bun.lock', 'manager' => FrontendPackageManager::Bun],
            ['lockfile' => 'package-lock.json', 'manager' => FrontendPackageManager::Npm],
        ];
    }

    private function path(string $file): string
    {
        return rtrim($this->basePath, '/') . '/' . $file;
    }
}
