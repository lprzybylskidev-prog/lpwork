<?php

declare(strict_types=1);

namespace LPWork\Config;

use LPWork\Config\Exceptions\ConfigCacheClearException;
use LPWork\Filesystem\Exceptions\FileDeleteException;
use LPWork\Filesystem\Filesystem;

use function ltrim;
use function rtrim;
use function str_starts_with;

/**
 * Represents the config cache framework component.
 */
final readonly class ConfigCache
{
    /**
     * Creates a new ConfigCache instance.
     */
    public function __construct(
        private string $basePath,
        private string $path = 'storage/framework/cache/config.php',
        private Filesystem $filesystem = new Filesystem(),
    ) {}

    /**
     * Returns path.
     */
    public function path(): string
    {
        if (str_starts_with($this->path, '/')) {
            return $this->path;
        }

        return rtrim($this->basePath, '/') . '/' . ltrim($this->path, '/');
    }

    /**
     * Reports whether exists.
     */
    public function exists(): bool
    {
        return $this->filesystem->isFile($this->path());
    }

    /**
     * Loads external input into this component.
     */
    public function load(): void
    {
        Config::initCached($this->path());
    }

    /**
     * Registers or stores write.
     */
    public function write(): void
    {
        Config::writeCache($this->path(), $this->filesystem);
    }

    /**
     * Clears the state owned by this component.
     */
    public function clear(): void
    {
        $path = $this->path();

        try {
            $this->filesystem->delete($path);
        } catch (FileDeleteException) {
            throw new ConfigCacheClearException($path);
        }
    }
}
