<?php

declare(strict_types=1);

namespace LPWork\Console\ProjectTasks;

use function is_dir;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function sort;

use SplFileInfo;

use function str_ends_with;
use function strlen;
use function substr;

/**
 * Represents the project file finder framework component.
 */
final readonly class ProjectFileFinder
{
    /**
     * Creates a new ProjectFileFinder instance.
     */
    public function __construct(
        private string $basePath,
    ) {}

    /**
     * @param list<string> $directories
     */
    public function hasPhpFiles(array $directories): bool
    {
        foreach ($directories as $directory) {
            foreach ($this->phpFiles($directory) as $file) {
                if ($file !== '') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param list<string> $directories
     *
     * @return list<string>
     */
    public function testFiles(array $directories): array
    {
        $tests = [];

        foreach ($directories as $directory) {
            foreach ($this->phpFiles($directory) as $file) {
                if (str_ends_with($file, 'Test.php')) {
                    $tests[] = $file;
                }
            }
        }

        sort($tests);

        return $tests;
    }

    /**
     * @return list<string>
     */
    private function phpFiles(string $directory): array
    {
        $path = $this->basePath . '/' . $directory;

        if (!is_dir($path)) {
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile()) {
                continue;
            }

            if (str_ends_with($file->getFilename(), '.php')) {
                $files[] = substr($file->getPathname(), strlen($this->basePath) + 1);
            }
        }

        sort($files);

        return $files;
    }
}
