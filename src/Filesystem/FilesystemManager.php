<?php
declare(strict_types=1);

namespace LPwork\Filesystem;

use LPwork\Filesystem\Exception\FilesystemNotFoundException;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;

/**
 * Manages named filesystem disks.
 */
class FilesystemManager
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $disks;

    /**
     * @var string
     */
    private string $default;

    /**
     * @var array<string, FilesystemOperator>
     */
    private array $operators = [];

    /**
     * @param array<string, array<string, mixed>> $disks
     * @param string                              $default
     */
    public function __construct(array $disks, string $default = "local")
    {
        $this->disks = $disks;
        $this->default = $default;
    }

    /**
     * Returns a filesystem operator by disk name.
     *
     * @param string|null $name
     *
     * @return FilesystemOperator
     */
    public function disk(?string $name = null): FilesystemOperator
    {
        $diskName = $name ?? $this->default;

        if (isset($this->operators[$diskName])) {
            return $this->operators[$diskName];
        }

        if (!isset($this->disks[$diskName])) {
            throw new FilesystemNotFoundException(
                \sprintf('Filesystem disk "%s" is not configured.', $diskName),
            );
        }

        $config = $this->disks[$diskName];
        $adapter = $this->createAdapter($config);
        $operator = new Filesystem($adapter);

        $this->operators[$diskName] = $operator;

        return $operator;
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return FilesystemAdapter
     */
    private function createAdapter(array $config): FilesystemAdapter
    {
        $driver = $config["driver"] ?? "local";

        if ($driver === "local") {
            $root = $config["root"] ?? null;

            if ($root === null || $root === "") {
                throw new FilesystemNotFoundException(
                    "Local filesystem root is not configured.",
                );
            }

            $normalizedRoot = $this->normalizeRoot($root);

            return new LocalFilesystemAdapter($normalizedRoot);
        }

        throw new FilesystemNotFoundException(
            \sprintf('Filesystem driver "%s" is not supported yet.', $driver),
        );
    }

    /**
     * Ensures filesystem root is absolute; relative paths are resolved from project root.
     *
     * @param string $root
     *
     * @return string
     */
    private function normalizeRoot(string $root): string
    {
        if ($this->isAbsolutePath($root)) {
            return $root;
        }

        return \dirname(__DIR__, 2) . "/" . \ltrim($root, "/");
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    private function isAbsolutePath(string $path): bool
    {
        if (\str_starts_with($path, "/")) {
            return true;
        }

        return (bool) \preg_match("/^[A-Za-z]:\\\\/", $path);
    }
}
