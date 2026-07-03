<?php

declare(strict_types=1);

namespace Tests\support\frontend;

use LPWork\Filesystem\Filesystem;

final readonly class FrontendPackageManagerTestWorkspace
{
    private const string RELATIVE_PATH = 'storage/testing/frontend-package-manager';

    private function __construct(
        private string $basePath,
        private Filesystem $files,
    ) {}

    public static function create(): self
    {
        $files = new Filesystem();
        $basePath = self::storagePath();

        $files->clearDirectory($basePath);
        $files->makeDirectory($basePath);

        return new self($basePath, $files);
    }

    public static function clear(): void
    {
        new Filesystem()->clearDirectory(self::storagePath());
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    public function files(): Filesystem
    {
        return $this->files;
    }

    public function writeLockfile(string $lockfile): void
    {
        $this->files->write($this->basePath . '/' . $lockfile, '');
    }

    private static function storagePath(): string
    {
        return \Tests\support\ProjectPaths::root() . '/' . self::RELATIVE_PATH;
    }
}
