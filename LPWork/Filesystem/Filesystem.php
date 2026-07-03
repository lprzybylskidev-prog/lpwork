<?php

declare(strict_types=1);

namespace LPWork\Filesystem;

use function chmod;

use Closure;

use function dirname;
use function explode;
use function fclose;

use const FILE_APPEND;

use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function flock;
use function fopen;
use function fwrite;
use function glob;
use function implode;
use function is_dir;
use function is_file;
use function is_readable;
use function is_writable;

use const LOCK_EX;
use const LOCK_UN;

use LPWork\Filesystem\Exceptions\DirectoryClearException;
use LPWork\Filesystem\Exceptions\DirectoryCreateException;
use LPWork\Filesystem\Exceptions\DirectoryReadException;
use LPWork\Filesystem\Exceptions\FileDeleteException;
use LPWork\Filesystem\Exceptions\FileNotFoundException;
use LPWork\Filesystem\Exceptions\FileReadException;
use LPWork\Filesystem\Exceptions\FileWriteException;
use LPWork\Filesystem\Exceptions\InvalidPathException;

use function mkdir;
use function preg_match;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function rmdir;
use function rtrim;

use SplFileInfo;

use function str_contains;
use function str_replace;
use function str_starts_with;
use function strlen;

use Throwable;

use function unlink;

/**
 * Represents the filesystem framework component.
 */
final class Filesystem
{
    /**
     * Reports whether exists.
     */
    public function exists(string $path): bool
    {
        return file_exists($this->safeLocalPath($path));
    }

    /**
     * Reports whether is file.
     */
    public function isFile(string $path): bool
    {
        return is_file($this->safeLocalPath($path));
    }

    /**
     * Reports whether is directory.
     */
    public function isDirectory(string $path): bool
    {
        return is_dir($this->safeLocalPath($path));
    }

    /**
     * Reports whether is readable.
     */
    public function isReadable(string $path): bool
    {
        return is_readable($this->safeLocalPath($path));
    }

    /**
     * @return list<string>
     */
    public function files(string $pattern): array
    {
        $pattern = $this->safeLocalPath($pattern);
        $files = glob($pattern);

        if ($files === false) {
            throw new DirectoryReadException($pattern);
        }

        return $files;
    }

    /**
     * Builds or returns read.
     */
    public function read(string $path): string
    {
        $path = $this->safeLocalPath($path);

        if (!is_file($path)) {
            throw new FileNotFoundException($path);
        }

        $contents = @file_get_contents($path);

        if ($contents === false) {
            throw new FileReadException($path);
        }

        return $contents;
    }

    /**
     * Registers or stores write.
     */
    public function write(string $path, string $contents): void
    {
        $path = $this->safeLocalPath($path);
        $this->makeDirectory(dirname($path));

        if (@file_put_contents($path, $contents, LOCK_EX) === false) {
            throw new FileWriteException($path);
        }
    }

    /**
     * Registers or stores write if missing.
     */
    public function writeIfMissing(string $path, string $contents): bool
    {
        $path = $this->safeLocalPath($path);
        $this->makeDirectory(dirname($path));

        if (file_exists($path)) {
            return false;
        }

        $handle = @fopen($path, 'x');

        if ($handle === false) {
            if (file_exists($path)) {
                return false;
            }

            throw new FileWriteException($path);
        }

        try {
            $written = @fwrite($handle, $contents);

            if ($written === false || $written < strlen($contents)) {
                throw new FileWriteException($path);
            }
        } finally {
            @fclose($handle);
        }

        return true;
    }

    /**
     * Registers or stores append.
     */
    public function append(string $path, string $contents): void
    {
        $path = $this->safeLocalPath($path);
        $this->makeDirectory(dirname($path));

        if (@file_put_contents($path, $contents, FILE_APPEND | LOCK_EX) === false) {
            throw new FileWriteException($path);
        }
    }

    /**
     * Returns a copy with with exclusive lock applied.
     */
    public function withExclusiveLock(string $path, Closure $callback): mixed
    {
        $path = $this->safeLocalPath($path);
        $this->makeDirectory(dirname($path));
        $handle = $this->lockHandle($path);

        if ($handle === false || !@flock($handle, LOCK_EX)) {
            if ($handle !== false) {
                @fclose($handle);
            }

            throw new FileWriteException($path);
        }

        try {
            return $callback();
        } finally {
            @flock($handle, LOCK_UN);
            @fclose($handle);
        }
    }

    /**
     * @return resource|false
     */
    private function lockHandle(string $path): mixed
    {
        if (is_file($path) && is_readable($path) && !is_writable($path)) {
            return @fopen($path, 'r');
        }

        $handle = @fopen($path, 'c');

        if ($handle !== false) {
            @chmod($path, 0o666);

            return $handle;
        }

        if (is_file($path) && is_readable($path)) {
            return @fopen($path, 'r');
        }

        return false;
    }

    /**
     * Deletes the requested value from this component's backing store.
     */
    public function delete(string $path): void
    {
        $path = $this->safeLocalPath($path);

        if (!file_exists($path)) {
            return;
        }

        if (!is_file($path) || !@unlink($path)) {
            throw new FileDeleteException($path);
        }
    }

    /**
     * Builds or returns make directory.
     */
    public function makeDirectory(string $path): void
    {
        $path = $this->safeLocalPath($path);

        if (is_dir($path)) {
            return;
        }

        if (!@mkdir($path, recursive: true) && !is_dir($path)) {
            throw new DirectoryCreateException($path);
        }
    }

    /**
     * Removes or clears clear directory.
     */
    public function clearDirectory(string $path): void
    {
        $path = $this->safeLocalPath($path);

        if (!is_dir($path)) {
            return;
        }

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            );

            foreach ($iterator as $item) {
                if (!$item instanceof SplFileInfo) {
                    throw new DirectoryClearException($path);
                }

                $itemPath = $item->getPathname();

                if ($item->isDir()) {
                    if (!@rmdir($itemPath)) {
                        throw new DirectoryClearException($path);
                    }

                    continue;
                }

                if (!@unlink($itemPath)) {
                    throw new DirectoryClearException($path);
                }
            }
        } catch (DirectoryClearException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw new DirectoryClearException($path);
        }
    }

    /**
     * Builds or returns resolve path.
     */
    public function resolvePath(string $root, string $path): string
    {
        return rtrim($this->safeLocalPath($root), '/') . '/' . $this->normalizeRelativePath($path);
    }

    /**
     * Builds or returns normalize relative path.
     */
    public function normalizeRelativePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);

        $this->assertPathIsLocal($path);

        if (str_starts_with($path, '/')) {
            throw InvalidPathException::absolute($path);
        }

        $segments = [];

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                throw InvalidPathException::traversal($path);
            }

            $segments[] = $segment;
        }

        if ($segments === []) {
            throw InvalidPathException::empty();
        }

        return implode('/', $segments);
    }

    private function safeLocalPath(string $path): string
    {
        if ($path === '') {
            throw InvalidPathException::empty();
        }

        $this->assertPathIsLocal($path);

        return $path;
    }

    private function assertPathIsLocal(string $path): void
    {
        if (str_contains($path, "\0")) {
            throw InvalidPathException::containsNullByte();
        }

        if (preg_match('/^[a-zA-Z][a-zA-Z0-9+.-]*:\/\//', $path) === 1) {
            throw InvalidPathException::stream($path);
        }
    }
}
