<?php

declare(strict_types=1);

namespace Tests\support;

use RuntimeException;

final class CacheTestFiles
{
    public static function createDirectory(): string
    {
        $directory = sys_get_temp_dir() . '/lpwork_cache_' . uniqid('', true);

        if (!mkdir($directory)) {
            throw new RuntimeException('Could not create temporary cache directory.');
        }

        return $directory;
    }

    public static function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if ($items === false) {
            throw new RuntimeException(sprintf('Could not read cache directory: %s', $directory));
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
