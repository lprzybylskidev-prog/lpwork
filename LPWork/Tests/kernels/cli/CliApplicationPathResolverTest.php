<?php

declare(strict_types=1);

use LPWork\Kernels\Cli\CliApplicationPathResolver;
use LPWork\Kernels\Cli\Exceptions\InvalidCliApplicationPathException;

afterAll(function (): void {
    Tests\support\ApplicationTestEnvironment::removeDirectories();
});

it('uses an explicit CLI base path when one is configured', function (): void {
    $environment = Tests\support\ApplicationTestEnvironment::create();
    $other = Tests\support\ApplicationTestEnvironment::create();
    $environment->writeFile('vendor/autoload.php', "<?php\n");
    $other->writeFile('vendor/autoload.php', "<?php\n");

    $path = new CliApplicationPathResolver($other->basePath())->resolve(
        workingDirectory: null,
        configuredBasePath: $environment->basePath(),
    );

    expect($path)->toBe($environment->basePath());
});

it('uses the entrypoint directory for the monolithic project checkout', function (): void {
    $environment = Tests\support\ApplicationTestEnvironment::create();
    $environment->writeFile('vendor/autoload.php', "<?php\n");

    $path = new CliApplicationPathResolver($environment->basePath())->resolve();

    expect($path)->toBe($environment->basePath());
});

it('finds an application root above the current working directory for installed package binaries', function (): void {
    $environment = Tests\support\ApplicationTestEnvironment::create();
    $environment->writeFile('vendor/autoload.php', "<?php\n");
    $environment->writeFile('vendor/lpwork/framework/.keep', '');
    $environment->writeFile('App/Console/Nested/.keep', '');
    $packageDirectory = $environment->basePath() . '/vendor/lpwork/framework';
    $nestedWorkingDirectory = $environment->basePath() . '/App/Console/Nested';

    $path = new CliApplicationPathResolver($packageDirectory)->resolve($nestedWorkingDirectory);

    expect($path)->toBe($environment->basePath());
});

it('rejects missing explicit CLI base paths', function (): void {
    expect(fn(): string => new CliApplicationPathResolver('/tmp')->resolve(
        configuredBasePath: '/tmp/lpwork-missing-application',
    ))->toThrow(
        InvalidCliApplicationPathException::class,
        'Cannot resolve CLI application path from LPWORK_BASE_PATH [/tmp/lpwork-missing-application]: directory does not exist.',
    );
});

it('explains when no application root can be located', function (): void {
    $environment = Tests\support\ApplicationTestEnvironment::create();
    $environment->writeFile('vendor/lpwork/framework/.keep', '');
    $outside = sys_get_temp_dir();

    expect(fn(): string => new CliApplicationPathResolver($environment->basePath() . '/vendor/lpwork/framework')->resolve($outside))
        ->toThrow(
            InvalidCliApplicationPathException::class,
            'Cannot locate an LPWork application root',
        );
});
