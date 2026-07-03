<?php

declare(strict_types=1);

namespace Tests\support\architecture;

use function basename;
use function is_dir;
use function ltrim;
use function str_ends_with;
use function str_replace;

final readonly class ApplicationModuleShapeScanner
{
    /**
     * @var list<string>
     */
    private const FORBIDDEN_TOP_LEVEL_DIRECTORIES = [
        'Broadcasting',
        'Console',
        'Controllers',
        'Database',
        'Events',
        'Health',
        'Jobs',
        'Listeners',
        'Middleware',
        'Notifications',
        'Providers',
        'Routes',
        'Schedule',
        'Translation',
        'Translations',
        'Validation',
        'View',
        'resources',
    ];

    /**
     * @param list<string> $directories
     *
     * @return list<string>
     */
    public static function violations(array $directories): array
    {
        $violations = [];

        foreach (self::applicationRoots($directories) as $appRoot) {
            foreach (self::FORBIDDEN_TOP_LEVEL_DIRECTORIES as $directory) {
                $path = $appRoot . '/' . $directory;

                if (is_dir($path)) {
                    $violations[] = "{$path} is a loose application directory. Put app-owned code under App/Modules/{Module}.";
                }
            }
        }

        foreach (PhpFileFinder::inDirectories($directories) as $file) {
            if (!self::isAllowedAppPhpFile($file)) {
                $violations[] = "{$file} is outside App/Modules, App/Shared, and App/AppServiceProvider.php.";
            }
        }

        return $violations;
    }

    public static function isAllowedAppPhpFile(string $path): bool
    {
        $path = self::normalizePath($path);
        $appPath = self::appRelativePath($path);

        if ($appPath === null) {
            return true;
        }

        return $appPath === 'AppServiceProvider.php'
            || str_starts_with($appPath, 'Shared/')
            || str_starts_with($appPath, 'Modules/');
    }

    /**
     * @param list<string> $directories
     *
     * @return list<string>
     */
    private static function applicationRoots(array $directories): array
    {
        $roots = [];

        foreach ($directories as $directory) {
            $directory = self::normalizePath($directory);

            if (basename($directory) === 'App') {
                $roots[] = $directory;

                continue;
            }

            if (is_dir($directory . '/App')) {
                $roots[] = $directory . '/App';
            }
        }

        return $roots;
    }

    private static function appRelativePath(string $path): ?string
    {
        if (str_ends_with($path, '/App')) {
            return '';
        }

        $marker = '/App/';
        $position = strpos($path, $marker);

        if ($position === false) {
            return null;
        }

        return ltrim(substr($path, $position + strlen($marker)), '/');
    }

    private static function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
