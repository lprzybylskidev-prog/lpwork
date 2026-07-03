<?php

declare(strict_types=1);

namespace Tests\support\architecture;

final readonly class ConfigUsageBoundaries
{
    public static function canUseConfig(string $path): bool
    {
        $path = self::normalizePath($path);

        return self::isConfigImplementationFile($path)
            || self::isTestFile($path)
            || self::isApplicationConfigFile($path)
            || self::isProviderFile($path)
            || self::isBootstrapFile($path)
            || self::isEntrypointOrKernelFallback($path)
            || self::isDiagnosticBoundary($path);
    }

    private static function isConfigImplementationFile(string $path): bool
    {
        return str_contains($path, '/LPWork/Config/');
    }

    private static function isTestFile(string $path): bool
    {
        return str_contains($path, '/LPWork/Tests/')
            || str_contains($path, '/LPWork/TestSupport/')
            || str_contains($path, '/tests/');
    }

    private static function isApplicationConfigFile(string $path): bool
    {
        return str_contains($path, '/App/Shared/Configs/');
    }

    private static function isProviderFile(string $path): bool
    {
        return str_contains($path, '/LPWork/') && str_contains($path, '/Providers/');
    }

    private static function isBootstrapFile(string $path): bool
    {
        return str_ends_with($path, '/LPWork/Bootstrap/Bootstrap.php');
    }

    private static function isEntrypointOrKernelFallback(string $path): bool
    {
        return str_ends_with($path, '/LPWork/Kernels/AbstractKernel.php')
            || str_ends_with($path, '/LPWork/Kernels/Http/HttpEntrypoint.php')
            || str_ends_with($path, '/LPWork/Kernels/Cli/CliEntrypoint.php');
    }

    private static function isDiagnosticBoundary(string $path): bool
    {
        return str_ends_with($path, '/LPWork/Console/Commands/ConfigShowCommand.php')
            || str_ends_with($path, '/LPWork/Console/Commands/AboutRuntimeSnapshotFactory.php')
            || str_ends_with($path, '/LPWork/ErrorHandling/ExceptionReporterFactory.php')
            || str_ends_with($path, '/LPWork/Health/Checks/PhpRuntimeHealthCheck.php');
    }

    private static function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
