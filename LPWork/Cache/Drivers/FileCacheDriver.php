<?php

declare(strict_types=1);

namespace LPWork\Cache\Drivers;

use Closure;

use function hash;
use function is_array;

use LPWork\Cache\Contracts\CacheDriver;
use LPWork\Cache\Exceptions\CacheClearException;
use LPWork\Cache\Exceptions\CacheReadException;
use LPWork\Cache\Exceptions\CacheWriteException;
use LPWork\Cache\Exceptions\InvalidCacheKeyException;
use LPWork\Cache\Exceptions\InvalidCacheTtlException;
use LPWork\Filesystem\Exceptions\DirectoryClearException;
use LPWork\Filesystem\Exceptions\FileDeleteException;
use LPWork\Filesystem\Exceptions\FileNotFoundException;
use LPWork\Filesystem\Exceptions\FileReadException;
use LPWork\Filesystem\Exceptions\FileWriteException;
use LPWork\Filesystem\Filesystem;
use LPWork\Storage\Exceptions\StorageFileNotFoundException;
use LPWork\Storage\StorageDisk;
use LPWork\Time\Contracts\Clock;
use LPWork\Time\SystemClock;

use function ltrim;
use function rtrim;
use function serialize;
use function str_starts_with;
use function unserialize;

/**
 * Represents the file cache driver framework component.
 */
final readonly class FileCacheDriver implements CacheDriver
{
    /**
     * Creates a new FileCacheDriver instance.
     */
    public function __construct(
        private string $path,
        private string $basePath,
        private ?StorageDisk $disk = null,
        private Filesystem $filesystem = new Filesystem(),
        private Clock $clock = new SystemClock(),
    ) {}

    /**
     * Returns the requested value from this component.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $path = $this->pathForKey($key);

        return $this->synchronized($path, function () use ($path, $default): mixed {
            if (!$this->exists($path)) {
                return $default;
            }

            $payload = $this->storedPayload($path);

            if ($this->expired($payload)) {
                $this->delete($path);

                return $default;
            }

            return $payload['value'];
        });
    }

    /**
     * Stores or replaces a value in this component's backing store.
     */
    public function put(string $key, mixed $value, ?int $ttlSeconds = null): void
    {
        $path = $this->pathForKey($key);
        $content = serialize($this->payload($value, $ttlSeconds));

        try {
            $this->write($path, $content);
        } catch (FileWriteException) {
            throw new CacheWriteException($path);
        }
    }

    /**
     * Adds an item to this component's registry or backing store.
     */
    public function add(string $key, mixed $value, int $ttlSeconds): bool
    {
        $this->assertValidTtl($ttlSeconds);

        $path = $this->pathForKey($key);

        $added = $this->synchronized($path, function () use ($path, $value, $ttlSeconds): bool {
            if ($this->exists($path)) {
                $stored = $this->storedPayload($path);

                if (!$this->expired($stored)) {
                    return false;
                }

                $this->delete($path);
            }

            try {
                return $this->writeIfMissing($path, serialize($this->payload($value, $ttlSeconds)));
            } catch (FileWriteException) {
                throw new CacheWriteException($path);
            }
        });

        return $added === true;
    }

    /**
     * Removes or clears forget if value.
     */
    public function forgetIfValue(string $key, mixed $value): bool
    {
        $path = $this->pathForKey($key);

        $forgotten = $this->synchronized($path, function () use ($path, $value): bool {
            if (!$this->exists($path)) {
                return false;
            }

            $payload = $this->storedPayload($path);

            if ($this->expired($payload)) {
                $this->delete($path);

                return false;
            }

            if ($payload['value'] !== $value) {
                return false;
            }

            $this->delete($path);

            return true;
        });

        return $forgotten === true;
    }

    /**
     * Removes a value from this component's backing store.
     */
    public function forget(string $key): void
    {
        $path = $this->pathForKey($key);

        try {
            $this->delete($path);
        } catch (FileDeleteException) {
            throw new CacheWriteException($path);
        }
    }

    /**
     * Clears the state owned by this component.
     */
    public function clear(): void
    {
        try {
            $this->clearStoragePath($this->path);
        } catch (DirectoryClearException) {
            throw new CacheClearException($this->path);
        }
    }

    private function pathForKey(string $key): string
    {
        if ($key === '') {
            throw new InvalidCacheKeyException();
        }

        return $this->path . '/' . hash('sha256', $key) . '.cache';
    }

    private function exists(string $path): bool
    {
        if ($this->disk !== null) {
            return $this->disk->exists($path);
        }

        return $this->filesystem->exists($this->absolutePath($path));
    }

    private function read(string $path): string
    {
        if ($this->disk !== null) {
            return $this->disk->get($path);
        }

        return $this->filesystem->read($this->absolutePath($path));
    }

    private function write(string $path, string $content): void
    {
        if ($this->disk !== null) {
            $this->disk->put($path, $content);

            return;
        }

        $this->filesystem->write($this->absolutePath($path), $content);
    }

    private function writeIfMissing(string $path, string $content): bool
    {
        if ($this->disk !== null) {
            return $this->disk->putIfMissing($path, $content);
        }

        return $this->filesystem->writeIfMissing($this->absolutePath($path), $content);
    }

    private function delete(string $path): void
    {
        if ($this->disk !== null) {
            $this->disk->delete($path);

            return;
        }

        $this->filesystem->delete($this->absolutePath($path));
    }

    private function clearStoragePath(string $path): void
    {
        if ($this->disk !== null) {
            $this->disk->clear($path);

            return;
        }

        $this->filesystem->clearDirectory($this->absolutePath($path));
    }

    private function absolutePath(string $path): string
    {
        if (str_starts_with($path, '/')) {
            return rtrim($path, '/');
        }

        return rtrim($this->basePath, '/') . '/' . ltrim(rtrim($path, '/'), '/');
    }

    private function synchronized(string $path, Closure $callback): mixed
    {
        $lockPath = $path . '.lock';

        if ($this->disk !== null) {
            return $this->disk->withExclusiveLock($lockPath, $callback);
        }

        return $this->filesystem->withExclusiveLock($this->absolutePath($lockPath), $callback);
    }

    /**
     * @return array{value: mixed, expires_at: int|null}
     */
    private function payload(mixed $value, ?int $ttlSeconds): array
    {
        if ($ttlSeconds !== null) {
            $this->assertValidTtl($ttlSeconds);
        }

        return [
            'value' => $value,
            'expires_at' => $ttlSeconds === null ? null : $this->now() + $ttlSeconds,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function storedPayload(string $path): array
    {
        try {
            $payload = @unserialize($this->read($path));
        } catch (FileNotFoundException|FileReadException|StorageFileNotFoundException) {
            throw new CacheReadException($path);
        }

        if (!is_array($payload) || !array_key_exists('value', $payload)) {
            throw new CacheReadException($path);
        }

        return [
            'value' => $payload['value'],
            'expires_at' => $payload['expires_at'] ?? null,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function expired(array $payload): bool
    {
        $expiresAt = $payload['expires_at'] ?? null;

        return is_int($expiresAt) && $expiresAt <= $this->now();
    }

    private function now(): int
    {
        return $this->clock->now()->getTimestamp();
    }

    private function assertValidTtl(int $ttlSeconds): void
    {
        if ($ttlSeconds <= 0) {
            throw new InvalidCacheTtlException();
        }
    }
}
