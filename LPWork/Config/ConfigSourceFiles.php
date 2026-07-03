<?php

declare(strict_types=1);

namespace LPWork\Config;

use LPWork\Config\Contracts\ConfigSource;
use LPWork\Config\Exceptions\FileNotFoundException;
use LPWork\Config\Exceptions\FileNotReadableException;
use LPWork\Config\Exceptions\InvalidFileException;
use LPWork\Filesystem\Filesystem;

/**
 * Represents the config source files framework component.
 */
final readonly class ConfigSourceFiles implements ConfigSource
{
    /**
     * @param list<string> $files
     */
    public function __construct(
        private array $files = [],
        private Filesystem $filesystem = new Filesystem(),
    ) {}

    /**
     * @return list<string>
     */
    public function all(): array
    {
        return $this->files;
    }

    /**
     * @return array<string, array<array-key, mixed>>
     */
    public function load(): array
    {
        $configs = [];

        foreach ($this->files as $config) {
            if (!$this->filesystem->exists($config)) {
                throw new FileNotFoundException($config);
            }

            if (!$this->filesystem->isReadable($config)) {
                throw new FileNotReadableException($config);
            }

            $configContent = include $config;

            if (!is_array($configContent)) {
                throw new InvalidFileException($config);
            }

            $configs[pathinfo($config, PATHINFO_FILENAME)] = $configContent;
        }

        return $configs;
    }
}
