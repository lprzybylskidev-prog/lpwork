<?php

declare(strict_types=1);

namespace Tests\support\console;

use LPWork\Console\Contracts\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;

final readonly class ProductionSensitiveCommandContracts
{
    /**
     * @return list<class-string<Command>>
     */
    public static function commandClasses(): array
    {
        $classes = [];
        $directory = \Tests\support\ProjectPaths::root() . '/LPWork';
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        foreach ($files as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile() || !str_ends_with($file->getFilename(), 'Command.php')) {
                continue;
            }

            if (self::isTestInfrastructureFile($file)) {
                continue;
            }

            $class = self::classFromFile($file);

            if ($class === null || !is_subclass_of($class, Command::class)) {
                continue;
            }

            $classes[] = $class;
        }

        sort($classes);

        return $classes;
    }

    /**
     * @param class-string<Command> $command
     */
    public static function instanceWithoutConstructor(string $command): Command
    {
        return new ReflectionClass($command)->newInstanceWithoutConstructor();
    }

    /**
     * @return class-string<Command>|null
     */
    private static function classFromFile(SplFileInfo $file): ?string
    {
        $path = $file->getPathname();
        $prefix = \Tests\support\ProjectPaths::root() . '/LPWork/';

        if (!str_starts_with($path, $prefix)) {
            return null;
        }

        $relative = substr($path, strlen($prefix), -4);
        $class = 'LPWork\\' . str_replace('/', '\\', $relative);

        if (!class_exists($class) || !is_subclass_of($class, Command::class)) {
            return null;
        }

        return $class;
    }

    private static function isTestInfrastructureFile(SplFileInfo $file): bool
    {
        $path = str_replace('\\', '/', $file->getPathname());

        return str_contains($path, '/LPWork/Tests/')
            || str_contains($path, '/LPWork/TestSupport/');
    }
}
