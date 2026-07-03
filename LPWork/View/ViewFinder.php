<?php

declare(strict_types=1);

namespace LPWork\View;

use function explode;
use function hash;
use function is_string;

use LPWork\Cache\CacheStore;
use LPWork\Filesystem\Exceptions\InvalidPathException;
use LPWork\Filesystem\Filesystem;
use LPWork\View\Exceptions\ViewNotFoundException;

use function str_contains;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use function trim;

/**
 * Represents the view finder framework component.
 */
final readonly class ViewFinder
{
    /**
     * @param list<string> $paths
     */
    public function __construct(
        private array $paths,
        private string $basePath,
        private ?CacheStore $cache = null,
        private string $extension = 'php',
        private ?ViewNamespaceRegistry $namespaces = null,
        private Filesystem $filesystem = new Filesystem(),
    ) {}

    /**
     * Performs the find operation.
     */
    public function find(string $name): string
    {
        [$paths, $relativePath] = $this->resolveLookup($name);
        $cacheKey = 'view.path.' . hash('sha256', $name . '|' . $relativePath);
        $cached = $this->cache?->get($cacheKey);

        if (is_string($cached) && $this->filesystem->isFile($cached)) {
            return $cached;
        }

        foreach ($paths as $path) {
            $candidate = $this->filesystem->resolvePath($this->absolutePath($path), $relativePath);

            if ($this->filesystem->isFile($candidate)) {
                $this->cache?->put($cacheKey, $candidate);

                return $candidate;
            }
        }

        throw ViewNotFoundException::forName($name, $paths);
    }

    /**
     * @return array{0: list<string>, 1: string}
     */
    private function resolveLookup(string $name): array
    {
        $name = trim($name);

        if ($name === '') {
            throw InvalidPathException::empty();
        }

        if (!str_contains($name, '::')) {
            return [$this->paths, $this->relativePath($name)];
        }

        [$namespace, $view] = explode('::', $name, 2);

        if ($namespace === '' || $view === '') {
            throw InvalidPathException::empty();
        }

        return [$this->namespaces?->paths($namespace) ?? [], $this->relativePath($view)];
    }

    private function relativePath(string $name): string
    {
        $name = trim($name);

        if ($name === '') {
            throw InvalidPathException::empty();
        }

        $path = str_replace('.', '/', $name);

        if (!str_ends_with($path, '.' . $this->extension)) {
            $path .= '.' . $this->extension;
        }

        return $this->filesystem->normalizeRelativePath($path);
    }

    private function absolutePath(string $path): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }

        return $this->filesystem->resolvePath($this->basePath, $path);
    }
}
