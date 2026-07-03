<?php

declare(strict_types=1);

namespace LPWork\Storage\Contracts;

use Closure;

/**
 * Defines filesystem-like operations required by storage disks.
 */
interface StorageDriver
{
    /**
     * Reports whether a path exists on the disk.
     */
    public function exists(string $path): bool;

    /**
     * Reads the contents of a file from the disk.
     */
    public function get(string $path): string;

    /**
     * Writes file contents to the disk, replacing any existing file at the path.
     */
    public function put(string $path, string $contents): void;

    /**
     * Writes file contents only when the path does not already exist.
     */
    public function putIfMissing(string $path, string $contents): bool;

    /**
     * Appends contents to a file on the disk.
     */
    public function append(string $path, string $contents): void;

    /**
     * Deletes one file from the disk.
     */
    public function delete(string $path): void;

    /**
     * Clears all files under the given disk path.
     */
    public function clear(string $path): void;

    /**
     * Runs the callback while holding an exclusive lock for the given path.
     */
    public function withExclusiveLock(string $path, Closure $callback): mixed;
}
