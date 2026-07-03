<?php

declare(strict_types=1);

use LPWork\Config\Config;
use LPWork\Console\Output;
use LPWork\Emitters\ConsoleEmitter;
use LPWork\Environment\Environment;
use LPWork\Kernels\Cli\CliEntrypoint;
use LPWork\Url\Url;
use Tests\support\ApplicationTestEnvironment;
use Tests\support\console\OutputStreams;

beforeEach(function (): void {
    Url::reset();
    Environment::reset();
    Config::reset();
});

afterEach(function (): void {
    Url::reset();
    Environment::reset();
    Config::reset();
});

afterAll(function (): void {
    ApplicationTestEnvironment::removeDirectories();
});

it('renders framework CLI errors for bootstrap failures before the kernel is available', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->setEnvValue('APP_KEY', 'short');
    $streams = OutputStreams::create();

    $exitCode = new CliEntrypoint(
        $environment->basePath(),
        ['lpwork', 'route:list'],
        new ConsoleEmitter(new Output($streams->stdout, $streams->stderr, decorated: false)),
    )->run();

    expect($exitCode)->toBe(1)
        ->and($streams->stdout())->toBe('')
        ->and($streams->stderr())->toContain('LPWork\Security\Exceptions\InvalidApplicationKeyException')
        ->and($streams->stderr())->toContain('APP_KEY must contain at least 32 bytes of secret material.');
});

it('keeps stale config cache recovery available through the CLI entrypoint', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->writeFile(
        'storage/framework/cache/config.php',
        "<?php\n\ndeclare(strict_types=1);\n\nreturn ['app' => ['env' => 'development', 'debug' => true, 'timezone' => 'UTC']];\n",
    );
    $streams = OutputStreams::create();

    try {
        $exitCode = new CliEntrypoint(
            $environment->basePath(),
            ['lpwork', 'route:list'],
            new ConsoleEmitter(new Output($streams->stdout, $streams->stderr, decorated: false)),
        )->run();

        expect($exitCode)->toBe(1)
            ->and($streams->stdout())->toBe('')
            ->and($streams->stderr())->toContain('The compiled configuration cache is invalid and must be cleared or rebuilt.')
            ->and($streams->stderr())->toContain('Only config:clear and config:cache are available')
            ->and($streams->stderr())->toContain('Command not available while the configuration cache is invalid: route:list');
    } finally {
        restore_error_handler();
        restore_exception_handler();
    }
});
