<?php

declare(strict_types=1);

namespace Tests\support\testing\Architecture;

use PHPUnit\Framework\Assert;
use Tests\support\architecture\ApplicationModuleShapeScanner;
use Tests\support\architecture\ConfigUsageScanner;
use Tests\support\architecture\EnvironmentUsageScanner;
use Tests\support\architecture\GlobalUsageScanner;
use Tests\support\architecture\StaticLifecycleScanner;

final readonly class ArchitectureAssertions
{
    /**
     * @param list<string>|null $directories
     */
    public static function assertNoGlobalUsageViolations(?array $directories = null): void
    {
        self::assertNoViolations(GlobalUsageScanner::violations($directories ?? self::runtimePaths()));
    }

    /**
     * @param list<string>|null $directories
     */
    public static function assertNoEnvironmentUsageViolations(?array $directories = null): void
    {
        self::assertNoViolations(EnvironmentUsageScanner::violations($directories ?? self::frameworkAndApplicationPaths()));
    }

    /**
     * @param list<string>|null $directories
     */
    public static function assertNoConfigUsageViolations(?array $directories = null): void
    {
        self::assertNoViolations(ConfigUsageScanner::violations($directories ?? self::frameworkAndApplicationPaths()));
    }

    /**
     * @param list<string>|null $directories
     */
    public static function assertStaticStateIsResettable(?array $directories = null): void
    {
        self::assertNoViolations(StaticLifecycleScanner::violations($directories ?? self::frameworkAndApplicationPaths()));
    }

    /**
     * @param list<string>|null $directories
     */
    public static function assertApplicationUsesModuleFirstShape(?array $directories = null): void
    {
        self::assertNoViolations(ApplicationModuleShapeScanner::violations($directories ?? [self::projectPath('App')]));
    }

    /**
     * @return list<string>
     */
    public static function frameworkAndApplicationPaths(): array
    {
        return [
            self::projectPath('LPWork'),
            self::projectPath('App'),
        ];
    }

    /**
     * @return list<string>
     */
    public static function runtimePaths(): array
    {
        return [
            ...self::frameworkAndApplicationPaths(),
            self::projectPath('public'),
        ];
    }

    private static function projectPath(string $path): string
    {
        return \Tests\support\ProjectPaths::root() . '/' . ltrim($path, '/');
    }

    /**
     * @param list<string> $violations
     */
    private static function assertNoViolations(array $violations): void
    {
        Assert::assertSame([], $violations, "Architecture violations:\n" . implode("\n", $violations));
    }
}
