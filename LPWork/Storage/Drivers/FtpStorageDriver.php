<?php

declare(strict_types=1);

namespace LPWork\Storage\Drivers;

use Closure;
use FTP\Connection as FtpConnection;
use LPWork\Filesystem\Filesystem;
use LPWork\Shared\Exceptions\MissingPhpExtensionException;
use LPWork\Storage\Contracts\StorageDriver;
use LPWork\Storage\Exceptions\StorageFileNotFoundException;
use RuntimeException;

/**
 * Represents the ftp storage driver framework component.
 */
final readonly class FtpStorageDriver implements StorageDriver
{
    /**
     * Creates a new FtpStorageDriver instance.
     */
    public function __construct(
        private string $host,
        private string $username,
        private string $password,
        private string $root = '',
        private int $port = 21,
        private int $timeoutSeconds = 30,
        private bool $ssl = false,
        private bool $passive = true,
        private Filesystem $filesystem = new Filesystem(),
    ) {}

    /**
     * Reports whether exists.
     */
    public function exists(string $path): bool
    {
        $ftp = $this->connection();
        $size = ftp_size($ftp, $this->path($path));
        ftp_close($ftp);

        return $size >= 0;
    }

    /**
     * Returns the requested value from this component.
     */
    public function get(string $path): string
    {
        $temp = tempnam(sys_get_temp_dir(), 'lpwork_ftp_');

        if ($temp === false) {
            throw new RuntimeException('Could not create temporary FTP download file.');
        }

        $ftp = $this->connection();
        $remote = $this->path($path);
        $read = ftp_get($ftp, $temp, $remote, FTP_BINARY);
        ftp_close($ftp);

        if (!$read) {
            throw new StorageFileNotFoundException($path);
        }

        $contents = file_get_contents($temp);
        @unlink($temp);

        if ($contents === false) {
            throw new RuntimeException(sprintf('Could not read FTP object [%s].', $path));
        }

        return $contents;
    }

    /**
     * Stores or replaces a value in this component's backing store.
     */
    public function put(string $path, string $contents): void
    {
        $temp = tempnam(sys_get_temp_dir(), 'lpwork_ftp_');

        if ($temp === false || file_put_contents($temp, $contents) === false) {
            throw new RuntimeException('Could not create temporary FTP upload file.');
        }

        $ftp = $this->connection();
        $remote = $this->path($path);
        $this->makeDirectory($ftp, dirname($remote));
        $stored = ftp_put($ftp, $remote, $temp, FTP_BINARY);
        ftp_close($ftp);
        @unlink($temp);

        if (!$stored) {
            throw new RuntimeException(sprintf('Could not write FTP object [%s].', $path));
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
        $ftp = $this->connection();
        @ftp_delete($ftp, $this->path($path));
        ftp_close($ftp);
    }

    /**
     * Clears the state owned by this component.
     */
    public function clear(string $path): void
    {
        $ftp = $this->connection();
        $this->clearDirectory($ftp, $this->path($path));
        ftp_close($ftp);
    }

    /**
     * Returns a copy with with exclusive lock applied.
     */
    public function withExclusiveLock(string $path, Closure $callback): mixed
    {
        return $callback();
    }

    /**
     */
    private function connection(): FtpConnection
    {
        if (!function_exists('ftp_connect')) {
            throw new MissingPhpExtensionException('ftp', 'storage.ftp');
        }

        $ftp = $this->ssl ? ftp_ssl_connect($this->host, $this->port, $this->timeoutSeconds) : ftp_connect($this->host, $this->port, $this->timeoutSeconds);

        if ($ftp === false || !ftp_login($ftp, $this->username, $this->password)) {
            throw new RuntimeException(sprintf('Could not connect to FTP host [%s].', $this->host));
        }

        ftp_pasv($ftp, $this->passive);

        return $ftp;
    }

    private function path(string $path): string
    {
        return trim($this->root . '/' . $this->filesystem->normalizeRelativePath($path), '/');
    }

    private function makeDirectory(FtpConnection $ftp, string $path): void
    {
        $parts = explode('/', trim($path, '/'));
        $current = '';

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            $current .= '/' . $part;
            @ftp_mkdir($ftp, $current);
        }
    }

    private function clearDirectory(FtpConnection $ftp, string $path): void
    {
        $items = ftp_nlist($ftp, $path);

        if (!is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            if (@ftp_delete($ftp, $item)) {
                continue;
            }

            $this->clearDirectory($ftp, $item);
            @ftp_rmdir($ftp, $item);
        }
    }
}
