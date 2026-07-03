<?php

declare(strict_types=1);

namespace LPWork\Storage;

use Closure;
use LPWork\Filesystem\Filesystem;
use LPWork\Storage\Contracts\StorageDriver;
use LPWork\Storage\Exceptions\StorageUrlNotConfiguredException;

/**
 * Provides file operations for one configured storage disk.
 */
final readonly class StorageDisk
{
    /**
     * Creates a disk wrapper around a concrete storage driver and optional public URL base.
     */
    public function __construct(
        public string $name,
        private StorageDriver $driver,
        private ?string $url = null,
        private Filesystem $filesystem = new Filesystem(),
    ) {}

    /**
     * Reports whether a path exists on the disk.
     */
    public function exists(string $path): bool
    {
        return $this->driver->exists($path);
    }

    /**
     * Reads the contents of a file from the disk.
     */
    public function get(string $path): string
    {
        return $this->driver->get($path);
    }

    /**
     * Writes file contents to the disk, replacing any existing file at the path.
     */
    public function put(string $path, string $contents): void
    {
        $this->driver->put($path, $contents);
    }

    /**
     * Writes file contents only when the path does not already exist.
     */
    public function putIfMissing(string $path, string $contents): bool
    {
        return $this->driver->putIfMissing($path, $contents);
    }

    /**
     * Appends contents to a file on the disk.
     */
    public function append(string $path, string $contents): void
    {
        $this->driver->append($path, $contents);
    }

    /**
     * Deletes one file from the disk.
     */
    public function delete(string $path): void
    {
        $this->driver->delete($path);
    }

    /**
     * Clears all files under the given disk path.
     */
    public function clear(string $path): void
    {
        $this->driver->clear($path);
    }

    /**
     * Runs the callback while holding an exclusive lock for the given path.
     */
    public function withExclusiveLock(string $path, Closure $callback): mixed
    {
        return $this->driver->withExclusiveLock($path, $callback);
    }

    /**
     * Builds a public URL for a path on disks configured with a URL base.
     */
    public function url(string $path): string
    {
        if ($this->url === null) {
            throw new StorageUrlNotConfiguredException($this->name);
        }

        return rtrim($this->url, '/') . '/' . $this->filesystem->normalizeRelativePath($path);
    }
}
