<?php

declare(strict_types=1);

use PHPUnit\Framework\AssertionFailedError;
use Tests\support\testing\Architecture\ArchitectureAssertions;
use Tests\support\testing\Filesystem\TestFilesystem;

it('asserts global usage boundaries through shared scanner setup', function (): void {
    $filesystem = TestFilesystem::create();

    try {
        $filesystem->write('Example.php', "<?php\n\nheader('X-Test: yes');\n");

        expect(fn() => ArchitectureAssertions::assertNoGlobalUsageViolations([$filesystem->root()]))
            ->toThrow(AssertionFailedError::class, 'calls header()');
    } finally {
        $filesystem->cleanup();
    }
});

it('asserts Environment usage boundaries through a shared allowlist', function (): void {
    $filesystem = TestFilesystem::create();

    try {
        $filesystem->write('Service.php', "<?php\n\nuse LPWork\\Environment\\Environment;\n\nEnvironment::getString('APP_ENV');\n");

        expect(fn() => ArchitectureAssertions::assertNoEnvironmentUsageViolations([$filesystem->root()]))
            ->toThrow(AssertionFailedError::class, 'uses Environment::getString()');
    } finally {
        $filesystem->cleanup();
    }
});

it('asserts Config usage boundaries through a shared allowlist', function (): void {
    $filesystem = TestFilesystem::create();

    try {
        $filesystem->write('Service.php', "<?php\n\nuse LPWork\\Config\\Config;\n\nConfig::getString('app.env');\n");

        expect(fn() => ArchitectureAssertions::assertNoConfigUsageViolations([$filesystem->root()]))
            ->toThrow(AssertionFailedError::class, 'uses Config::getString()');
    } finally {
        $filesystem->cleanup();
    }
});

it('asserts application module-first shape through a shared scanner', function (): void {
    $filesystem = TestFilesystem::create();

    try {
        $filesystem->write('App/Controllers/DashboardController.php', "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Controllers;\n\nfinal class DashboardController {}\n");

        expect(fn() => ArchitectureAssertions::assertApplicationUsesModuleFirstShape([$filesystem->root()]))
            ->toThrow(AssertionFailedError::class, 'App/Controllers is a loose application directory');
    } finally {
        $filesystem->cleanup();
    }
});

it('accepts application modules configs and the root application provider', function (): void {
    $filesystem = TestFilesystem::create();

    try {
        $filesystem->write('App/AppServiceProvider.php', "<?php\n\ndeclare(strict_types=1);\n\nnamespace App;\n\nfinal class AppServiceProvider {}\n");
        $filesystem->write('App/Shared/Configs/AppConfig.php', "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Shared\\Configs;\n\nfinal class AppConfig {}\n");
        $filesystem->write('App/Shared/lang/en_US.json', '{}');
        $filesystem->write('App/Modules/Blog/Controllers/PostController.php', "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Modules\\Blog\\Controllers;\n\nfinal class PostController {}\n");

        ArchitectureAssertions::assertApplicationUsesModuleFirstShape([$filesystem->root()]);
    } finally {
        $filesystem->cleanup();
    }
});

it('asserts static state reset lifecycle rules', function (): void {
    $filesystem = TestFilesystem::create();

    try {
        $filesystem->write('Stateful.php', "<?php\n\nfinal class Stateful\n{\n    private static ?string \$value = null;\n}\n");

        expect(fn() => ArchitectureAssertions::assertStaticStateIsResettable([$filesystem->root()]))
            ->toThrow(AssertionFailedError::class, 'has static state without public reset()');
    } finally {
        $filesystem->cleanup();
    }
});

it('accepts static state with an explicit reset lifecycle', function (): void {
    $filesystem = TestFilesystem::create();

    try {
        $filesystem->write('Stateful.php', "<?php\n\nfinal class Stateful\n{\n    private static ?string \$value = null;\n\n    public static function reset(): void\n    {\n        self::\$value = null;\n    }\n}\n");

        ArchitectureAssertions::assertStaticStateIsResettable([$filesystem->root()]);
    } finally {
        $filesystem->cleanup();
    }
});
