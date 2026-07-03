<?php

declare(strict_types=1);

namespace LPWork\Storage\Drivers;

use Closure;
use LPWork\Filesystem\Filesystem;
use LPWork\Storage\Contracts\StorageDriver;

/**
 * Represents the local storage driver framework component.
 */
final readonly class LocalStorageDriver implements StorageDriver
{
    /**
     * Creates a new LocalStorageDriver instance.
     */
    public function __construct(
        private string $root,
        private Filesystem $filesystem = new Filesystem(),
    ) {}

    /**
     * Reports whether exists.
     */
    public function exists(string $path): bool
    {
        return $this->filesystem->exists($this->path($path));
    }

    /**
     * Returns the requested value from this component.
     */
    public function get(string $path): string
    {
        return $this->filesystem->read($this->path($path));
    }

    /**
     * Stores or replaces a value in this component's backing store.
     */
    public function put(string $path, string $contents): void
    {
        $this->filesystem->write($this->path($path), $contents);
    }

    /**
     * Registers or stores put if missing.
     */
    public function putIfMissing(string $path, string $contents): bool
    {
        return $this->filesystem->writeIfMissing($this->path($path), $contents);
    }

    /**
     * Registers or stores append.
     */
    public function append(string $path, string $contents): void
    {
        $this->filesystem->append($this->path($path), $contents);
    }

    /**
     * Deletes the requested value from this component's backing store.
     */
    public function delete(string $path): void
    {
        $this->filesystem->delete($this->path($path));
    }

    /**
     * Clears the state owned by this component.
     */
    public function clear(string $path): void
    {
        $this->filesystem->clearDirectory($this->path($path));
    }

    /**
     * Returns a copy with with exclusive lock applied.
     */
    public function withExclusiveLock(string $path, Closure $callback): mixed
    {
        return $this->filesystem->withExclusiveLock($this->path($path), $callback);
    }

    private function path(string $path): string
    {
        return $this->filesystem->resolvePath($this->root, $path);
    }
}
