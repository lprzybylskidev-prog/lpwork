<?php

declare(strict_types=1);

namespace LPWork\Storage\Drivers;

use Closure;
use LPWork\Filesystem\Filesystem;
use LPWork\Storage\Contracts\StorageDriver;
use LPWork\Storage\Exceptions\StorageFileNotFoundException;

/**
 * Represents the memory storage driver framework component.
 */
final class MemoryStorageDriver implements StorageDriver
{
    /**
     * @var array<string, string>
     */
    private array $files = [];

    /**
     * Creates a new MemoryStorageDriver instance.
     */
    public function __construct(private readonly Filesystem $filesystem = new Filesystem()) {}

    /**
     * Reports whether exists.
     */
    public function exists(string $path): bool
    {
        return array_key_exists($this->path($path), $this->files);
    }

    /**
     * Returns the requested value from this component.
     */
    public function get(string $path): string
    {
        $path = $this->path($path);

        if (!array_key_exists($path, $this->files)) {
            throw new StorageFileNotFoundException($path);
        }

        return $this->files[$path];
    }

    /**
     * Stores or replaces a value in this component's backing store.
     */
    public function put(string $path, string $contents): void
    {
        $this->files[$this->path($path)] = $contents;
    }

    /**
     * Registers or stores put if missing.
     */
    public function putIfMissing(string $path, string $contents): bool
    {
        $path = $this->path($path);

        if (array_key_exists($path, $this->files)) {
            return false;
        }

        $this->files[$path] = $contents;

        return true;
    }

    /**
     * Registers or stores append.
     */
    public function append(string $path, string $contents): void
    {
        $path = $this->path($path);

        $this->files[$path] = ($this->files[$path] ?? '') . $contents;
    }

    /**
     * Deletes the requested value from this component's backing store.
     */
    public function delete(string $path): void
    {
        unset($this->files[$this->path($path)]);
    }

    /**
     * Clears the state owned by this component.
     */
    public function clear(string $path): void
    {
        $path = $this->path($path);
        $prefix = $path . '/';

        foreach (array_keys($this->files) as $file) {
            if ($file === $path || str_starts_with($file, $prefix)) {
                unset($this->files[$file]);
            }
        }
    }

    /**
     * Returns a copy with with exclusive lock applied.
     */
    public function withExclusiveLock(string $path, Closure $callback): mixed
    {
        return $callback();
    }

    private function path(string $path): string
    {
        return $this->filesystem->normalizeRelativePath($path);
    }
}
