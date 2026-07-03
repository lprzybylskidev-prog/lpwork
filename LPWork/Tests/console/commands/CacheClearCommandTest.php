<?php

declare(strict_types=1);

use LPWork\Cache\CacheClearer;
use LPWork\Cache\CacheManager;
use LPWork\Config\Config;
use LPWork\Console\Commands\CacheClearCommand;
use LPWork\Console\ConsoleMiddlewarePipeline;
use LPWork\Console\Input;
use LPWork\Console\Middleware\ProductionSafetyMiddleware;
use LPWork\Console\Output;
use LPWork\Responses\ConsoleResponse;
use Tests\support\CacheTestFiles;
use Tests\support\ConfigTestFiles;
use Tests\support\console\OutputStreams;

beforeEach(function (): void {
    Config::reset();
    ConfigTestFiles::resetDirectory();
});

afterEach(function (): void {
    Config::reset();
});

afterAll(function (): void {
    ConfigTestFiles::removeDirectories();
});

it('clears all cache stores by default and reports cleared targets', function (): void {
    $basePath = CacheTestFiles::createDirectory();

    try {
        $manager = new CacheManager([
            'default' => 'framework',
            'stores' => [
                'framework' => [
                    'driver' => 'file',
                    'path' => 'storage/framework/cache',
                ],
                'views' => [
                    'driver' => 'file',
                    'path' => 'storage/framework/views',
                ],
            ],
        ], $basePath);
        $streams = OutputStreams::create();

        $manager->store('framework')->put('framework-key', 'cached');
        $manager->store('views')->put('view-key', 'cached');

        $exitCode = new CacheClearCommand(new CacheClearer($manager))->handle(
            new Input(['lpwork', 'cache:clear']),
            new Output($streams->stdout, $streams->stderr, decorated: false),
        );

        expect($exitCode)->toBe(0)
            ->and($streams->stdout())->toContain('Cache cleared: framework, views.')
            ->and($streams->stderr())->toBe('')
            ->and($manager->store('framework')->get('framework-key', 'missing'))->toBe('missing')
            ->and($manager->store('views')->get('view-key', 'missing'))->toBe('missing');
    } finally {
        CacheTestFiles::removeDirectory($basePath);
    }
});

it('refuses to clear cache in production without force', function (): void {
    ConfigTestFiles::createConfig('app.php', ['env' => 'production']);
    Config::init(ConfigTestFiles::directory());
    $basePath = CacheTestFiles::createDirectory();

    try {
        $manager = new CacheManager([
            'default' => 'framework',
            'stores' => [
                'framework' => [
                    'driver' => 'file',
                    'path' => 'storage/framework/cache',
                ],
            ],
        ], $basePath);
        $command = new CacheClearCommand(new CacheClearer($manager));
        $streams = OutputStreams::create();

        $manager->store('framework')->put('framework-key', 'cached');

        $response = new ConsoleMiddlewarePipeline([new ProductionSafetyMiddleware($command, true)])
            ->handle(
                new Input(['lpwork', 'cache:clear']),
                static fn(Input $input): ConsoleResponse => ConsoleResponse::using(
                    static fn(Output $output): int => $command->handle($input, $output),
                ),
            );

        expect($response->send(new Output($streams->stdout, $streams->stderr, decorated: false)))->toBe(1)
            ->and($streams->stdout())->toBe('')
            ->and($streams->stderr())->toBe("Refusing to clear cache in production without --force.\n")
            ->and($manager->store('framework')->get('framework-key'))->toBe('cached');
    } finally {
        CacheTestFiles::removeDirectory($basePath);
    }
});

it('clears cache in production when force is present', function (): void {
    ConfigTestFiles::createConfig('app.php', ['env' => 'production']);
    Config::init(ConfigTestFiles::directory());
    $basePath = CacheTestFiles::createDirectory();

    try {
        $manager = new CacheManager([
            'default' => 'framework',
            'stores' => [
                'framework' => [
                    'driver' => 'file',
                    'path' => 'storage/framework/cache',
                ],
            ],
        ], $basePath);
        $command = new CacheClearCommand(new CacheClearer($manager));
        $streams = OutputStreams::create();

        $manager->store('framework')->put('framework-key', 'cached');

        $response = new ConsoleMiddlewarePipeline([new ProductionSafetyMiddleware($command, true)])
            ->handle(
                new Input(['lpwork', 'cache:clear', '--force']),
                static fn(Input $input): ConsoleResponse => ConsoleResponse::using(
                    static fn(Output $output): int => $command->handle($input, $output),
                ),
            );

        expect($response->send(new Output($streams->stdout, $streams->stderr, decorated: false)))->toBe(0)
            ->and($streams->stdout())->toContain('Cache cleared: framework.')
            ->and($streams->stderr())->toBe('')
            ->and($manager->store('framework')->get('framework-key', 'missing'))->toBe('missing');
    } finally {
        CacheTestFiles::removeDirectory($basePath);
    }
});

it('clears cache in development without force', function (): void {
    ConfigTestFiles::createConfig('app.php', ['env' => 'local']);
    Config::init(ConfigTestFiles::directory());
    $basePath = CacheTestFiles::createDirectory();

    try {
        $manager = new CacheManager([
            'default' => 'framework',
            'stores' => [
                'framework' => [
                    'driver' => 'file',
                    'path' => 'storage/framework/cache',
                ],
            ],
        ], $basePath);
        $command = new CacheClearCommand(new CacheClearer($manager));
        $streams = OutputStreams::create();

        $manager->store('framework')->put('framework-key', 'cached');

        $response = new ConsoleMiddlewarePipeline([new ProductionSafetyMiddleware($command, false)])
            ->handle(
                new Input(['lpwork', 'cache:clear']),
                static fn(Input $input): ConsoleResponse => ConsoleResponse::using(
                    static fn(Output $output): int => $command->handle($input, $output),
                ),
            );

        expect($response->send(new Output($streams->stdout, $streams->stderr, decorated: false)))->toBe(0)
            ->and($streams->stdout())->toContain('Cache cleared: framework.')
            ->and($streams->stderr())->toBe('')
            ->and($manager->store('framework')->get('framework-key', 'missing'))->toBe('missing');
    } finally {
        CacheTestFiles::removeDirectory($basePath);
    }
});

it('clears a selected cache store and reports the target', function (): void {
    $basePath = CacheTestFiles::createDirectory();

    try {
        $manager = new CacheManager([
            'default' => 'framework',
            'stores' => [
                'framework' => [
                    'driver' => 'file',
                    'path' => 'storage/framework/cache',
                ],
                'views' => [
                    'driver' => 'file',
                    'path' => 'storage/framework/views',
                ],
            ],
        ], $basePath);
        $streams = OutputStreams::create();

        $manager->store('framework')->put('framework-key', 'cached');
        $manager->store('views')->put('view-key', 'cached');

        $exitCode = new CacheClearCommand(new CacheClearer($manager))->handle(
            new Input(['lpwork', 'cache:clear', 'views']),
            new Output($streams->stdout, $streams->stderr, decorated: false),
        );

        expect($exitCode)->toBe(0)
            ->and($streams->stdout())->toContain('Cache cleared: views.')
            ->and($streams->stderr())->toBe('')
            ->and($manager->store('framework')->get('framework-key'))->toBe('cached')
            ->and($manager->store('views')->get('view-key', 'missing'))->toBe('missing');
    } finally {
        CacheTestFiles::removeDirectory($basePath);
    }
});
