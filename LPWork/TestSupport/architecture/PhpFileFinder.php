<?php

declare(strict_types=1);

namespace Tests\support\architecture;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final readonly class PhpFileFinder
{
    /**
     * @param list<string> $directories
     *
     * @return list<string>
     */
    public static function inDirectories(array $directories): array
    {
        $files = [];

        foreach ($directories as $directory) {
            $files = [
                ...$files,
                ...self::inDirectory($directory),
            ];
        }

        sort($files);

        return $files;
    }

    /**
     * @return list<string>
     */
    private static function inDirectory(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || $file->getExtension() !== 'php') {
                continue;
            }

            $files[] = $file->getPathname();
        }

        return $files;
    }
}
