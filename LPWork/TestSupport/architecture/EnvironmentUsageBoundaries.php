<?php

declare(strict_types=1);

namespace Tests\support\architecture;

final readonly class EnvironmentUsageBoundaries
{
    public static function canUseEnvironment(string $path, string $method): bool
    {
        $path = self::normalizePath($path);

        if (self::isEnvironmentImplementationFile($path)) {
            return true;
        }

        if (self::isTestFile($path)) {
            return true;
        }

        if (self::isConfigFile($path)) {
            return true;
        }

        if (self::isEnvironmentConfigurationValidator($path)) {
            return true;
        }

        return self::isBootstrapFile($path) && $method === 'init';
    }

    private static function isBootstrapFile(string $path): bool
    {
        return str_ends_with($path, '/LPWork/Bootstrap/Bootstrap.php');
    }

    private static function isEnvironmentImplementationFile(string $path): bool
    {
        return str_contains($path, '/LPWork/Environment/');
    }

    private static function isEnvironmentConfigurationValidator(string $path): bool
    {
        return str_ends_with($path, '/LPWork/Config/EnvironmentConfigurationValidator.php');
    }

    private static function isConfigFile(string $path): bool
    {
        return str_contains($path, '/App/Shared/Configs/');
    }

    private static function isTestFile(string $path): bool
    {
        return str_contains($path, '/LPWork/Tests/')
            || str_contains($path, '/LPWork/TestSupport/')
            || str_contains($path, '/tests/');
    }

    private static function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
