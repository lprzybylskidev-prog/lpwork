<?php

declare(strict_types=1);

namespace Tests\support\foundation;

use RuntimeException;

final class ProviderTestFiles
{
    /**
     * @var list<string>
     */
    private static array $directories = [];

    public static function createDirectory(): string
    {
        $path = sys_get_temp_dir() . '/lpwork_provider_' . uniqid('', true);

        if (!mkdir($path)) {
            throw new RuntimeException('Could not create temporary provider directory.');
        }

        self::$directories[] = $path;

        return $path;
    }

    public static function createFile(string $fileName, string $content, ?string $directory = null): string
    {
        $directory ??= self::createDirectory();
        $path = $directory . '/' . $fileName;
        $fileDirectory = dirname($path);

        if (!is_dir($fileDirectory) && !mkdir($fileDirectory, recursive: true)) {
            throw new RuntimeException(sprintf('Could not create provider file directory: %s', $fileDirectory));
        }

        file_put_contents($path, $content);

        return $path;
    }

    public static function removeDirectories(): void
    {
        foreach (self::$directories as $directory) {
            self::removeDirectory($directory);
        }

        self::$directories = [];
    }

    private static function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if ($items === false) {
            throw new RuntimeException(sprintf('Could not read provider directory: %s', $directory));
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . '/' . $item;

            if (is_dir($path)) {
                self::removeDirectory($path);

                continue;
            }

            if (is_file($path)) {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
