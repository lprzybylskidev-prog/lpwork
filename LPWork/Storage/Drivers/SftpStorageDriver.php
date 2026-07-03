<?php

declare(strict_types=1);

namespace LPWork\Storage\Drivers;

use Closure;
use LPWork\Filesystem\Filesystem;
use LPWork\Shared\Exceptions\MissingPhpExtensionException;
use LPWork\Storage\Contracts\StorageDriver;
use LPWork\Storage\Exceptions\StorageFileNotFoundException;
use RuntimeException;

/**
 * Represents the sftp storage driver framework component.
 */
final readonly class SftpStorageDriver implements StorageDriver
{
    /**
     * Creates a new SftpStorageDriver instance.
     */
    public function __construct(
        private string $host,
        private string $username,
        private string $password,
        private string $root = '',
        private int $port = 22,
        private int $timeoutSeconds = 30,
        private Filesystem $filesystem = new Filesystem(),
    ) {}

    /**
     * Reports whether exists.
     */
    public function exists(string $path): bool
    {
        return file_exists($this->uri($path));
    }

    /**
     * Returns the requested value from this component.
     */
    public function get(string $path): string
    {
        $contents = @file_get_contents($this->uri($path));

        if ($contents === false) {
            throw new StorageFileNotFoundException($path);
        }

        return $contents;
    }

    /**
     * Stores or replaces a value in this component's backing store.
     */
    public function put(string $path, string $contents): void
    {
        $uri = $this->uri($path);
        $this->makeDirectory(dirname($uri));

        if (@file_put_contents($uri, $contents) === false) {
            throw new RuntimeException(sprintf('Could not write SFTP object [%s].', $path));
        }
    }

    /**
     * Registers or stores put if missing.
     */
    public function putIfMissing(string $path, string $contents): bool
    {
        if ($this->exists($path)) {
            return false;
        }

        $this->put($path, $contents);

        return true;
    }

    /**
     * Registers or stores append.
     */
    public function append(string $path, string $contents): void
    {
        $this->put($path, ($this->exists($path) ? $this->get($path) : '') . $contents);
    }

    /**
     * Deletes the requested value from this component's backing store.
     */
    public function delete(string $path): void
    {
        @unlink($this->uri($path));
    }

    /**
     * Clears the state owned by this component.
     */
    public function clear(string $path): void
    {
        $this->clearDirectory($this->uri($path));
    }

    /**
     * Returns a copy with with exclusive lock applied.
     */
    public function withExclusiveLock(string $path, Closure $callback): mixed
    {
        return $callback();
    }

    private function uri(string $path): string
    {
        $sftp = $this->sftp();

        return 'ssh2.sftp://' . intval($sftp) . '/' . $this->remotePath($path);
    }

    private function remotePath(string $path): string
    {
        return trim($this->root . '/' . $this->filesystem->normalizeRelativePath($path), '/');
    }

    /**
     * @return resource
     */
    private function sftp(): mixed
    {
        if (!function_exists('ssh2_connect')) {
            throw new MissingPhpExtensionException('ssh2', 'storage.sftp');
        }

        $connection = @ssh2_connect($this->host, $this->port, [], ['timeout' => $this->timeoutSeconds]);

        if ($connection === false || !@ssh2_auth_password($connection, $this->username, $this->password)) {
            throw new RuntimeException(sprintf('Could not connect to SFTP host [%s].', $this->host));
        }

        $sftp = @ssh2_sftp($connection);

        if ($sftp === false) {
            throw new RuntimeException(sprintf('Could not initialize SFTP subsystem for [%s].', $this->host));
        }

        return $sftp;
    }

    private function makeDirectory(string $uri): void
    {
        $parts = explode('/', trim($uri, '/'));
        $current = str_starts_with($uri, 'ssh2.sftp://') ? array_shift($parts) . '//' . array_shift($parts) : '';

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            $current .= '/' . $part;
            @mkdir($current);
        }
    }

    private function clearDirectory(string $uri): void
    {
        $items = @scandir($uri);

        if (!is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $child = rtrim($uri, '/') . '/' . $item;

            if (@unlink($child)) {
                continue;
            }

            $this->clearDirectory($child);
            @rmdir($child);
        }
    }
}
