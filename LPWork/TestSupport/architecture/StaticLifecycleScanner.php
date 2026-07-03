<?php

declare(strict_types=1);

namespace Tests\support\architecture;

final readonly class StaticLifecycleScanner
{
    /**
     * @param list<string> $directories
     *
     * @return list<string>
     */
    public static function violations(array $directories): array
    {
        $violations = [];

        foreach (PhpFileFinder::inDirectories($directories) as $path) {
            if (self::isTestFile($path)) {
                continue;
            }

            if (!self::hasStaticProperty($path)) {
                continue;
            }

            if (self::hasPublicResetMethod($path)) {
                continue;
            }

            $violations[] = sprintf('%s has static state without public reset()', $path);
        }

        sort($violations);

        return $violations;
    }

    private static function hasStaticProperty(string $path): bool
    {
        $content = file_get_contents($path);

        if (!is_string($content)) {
            return false;
        }

        return preg_match('/\b(?:private|protected|public)\s+static\s+(?!function\b)[^;=]*\$[A-Za-z_][A-Za-z0-9_]*/', $content) === 1;
    }

    private static function hasPublicResetMethod(string $path): bool
    {
        $content = file_get_contents($path);

        if (!is_string($content)) {
            return false;
        }

        return preg_match('/\bpublic\s+static\s+function\s+reset\s*\(/', $content) === 1;
    }

    private static function isTestFile(string $path): bool
    {
        $normalized = str_replace('\\', '/', $path);

        return str_contains($normalized, '/LPWork/Tests/')
            || str_contains($normalized, '/LPWork/TestSupport/')
            || str_contains($normalized, '/tests/');
    }
}
