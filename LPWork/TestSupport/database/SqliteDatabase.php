<?php

declare(strict_types=1);

namespace Tests\support\database;

use RuntimeException;

final class SqliteDatabase
{
    private function __construct(
        private readonly string $basePath,
    ) {}

    public static function create(): self
    {
        $basePath = sys_get_temp_dir() . '/lpwork_database_' . uniqid('', true);

        if (!mkdir($basePath . '/storage', recursive: true)) {
            throw new RuntimeException('Could not create temporary database directory.');
        }

        return new self($basePath);
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    public function relativePath(): string
    {
        return 'storage/database.sqlite';
    }

    public function absolutePath(): string
    {
        return $this->basePath . '/' . $this->relativePath();
    }

    public function remove(): void
    {
        $this->removeDirectory($this->basePath);
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if ($items === false) {
            throw new RuntimeException(sprintf('Could not read directory: %s', $directory));
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . '/' . $item;

            if (is_dir($path)) {
                $this->removeDirectory($path);

                continue;
            }

            if (!unlink($path)) {
                throw new RuntimeException(sprintf('Could not remove file: %s', $path));
            }
        }

        if (!rmdir($directory)) {
            throw new RuntimeException(sprintf('Could not remove directory: %s', $directory));
        }
    }
}
