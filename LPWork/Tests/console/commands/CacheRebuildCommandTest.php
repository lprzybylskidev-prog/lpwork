<?php

declare(strict_types=1);

use LPWork\Console\Commands\CacheRebuildCommand;
use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Foundation\CompiledCacheRegistry;
use Tests\support\console\OutputStreams;
use Tests\support\foundation\FakeCompiledCache;

it('rebuilds all registered compiled caches', function (): void {
    $caches = new CompiledCacheRegistry();
    $streams = OutputStreams::create();
    $config = new FakeCompiledCache('config', 'Configuration cache');
    $routes = new FakeCompiledCache('routes', 'Route cache');
    $translations = new FakeCompiledCache('translations', 'Translation cache');

    $caches->add($config);
    $caches->add($routes);
    $caches->add($translations);

    $exitCode = new CacheRebuildCommand($caches)->handle(
        new Input(['lpwork', 'cache:rebuild']),
        new Output($streams->stdout, $streams->stderr, decorated: false),
    );

    expect($exitCode)->toBe(0)
        ->and($config->rebuilds)->toBe(1)
        ->and($routes->rebuilds)->toBe(1)
        ->and($translations->rebuilds)->toBe(1)
        ->and($streams->stdout())->toContain('OK Configuration cache rebuilt successfully.')
        ->and($streams->stdout())->toContain('OK Route cache rebuilt successfully.')
        ->and($streams->stdout())->toContain('OK Translation cache rebuilt successfully.')
        ->and($streams->stdout())->toContain('OK Framework caches rebuilt.')
        ->and($streams->stderr())->toBe('');
});

it('rebuilds only selected caches', function (): void {
    $caches = new CompiledCacheRegistry();
    $streams = OutputStreams::create();
    $config = new FakeCompiledCache('config', 'Configuration cache', ['configuration', 'config:cache']);
    $routes = new FakeCompiledCache('routes', 'Route cache', ['route', 'route:cache']);
    $translations = new FakeCompiledCache('translations', 'Translation cache', ['translation', 'translation:cache']);

    $caches->add($config);
    $caches->add($routes);
    $caches->add($translations);

    $exitCode = new CacheRebuildCommand($caches)->handle(
        new Input(['lpwork', 'cache:rebuild', '--only=routes', '--only=translations']),
        new Output($streams->stdout, $streams->stderr, decorated: false),
    );

    expect($exitCode)->toBe(0)
        ->and($config->rebuilds)->toBe(0)
        ->and($routes->rebuilds)->toBe(1)
        ->and($translations->rebuilds)->toBe(1)
        ->and($streams->stderr())->toBe('');
});

it('accepts cache command aliases for selected rebuilds', function (): void {
    $caches = new CompiledCacheRegistry();
    $streams = OutputStreams::create();
    $config = new FakeCompiledCache('config', 'Configuration cache', ['configuration', 'config:cache']);

    $caches->add($config);

    $exitCode = new CacheRebuildCommand($caches)->handle(
        new Input(['lpwork', 'cache:rebuild', '--only=config:cache']),
        new Output($streams->stdout, $streams->stderr, decorated: false),
    );

    expect($exitCode)->toBe(0)
        ->and($config->rebuilds)->toBe(1)
        ->and($streams->stderr())->toBe('');
});

it('rejects unknown selected caches', function (): void {
    $caches = new CompiledCacheRegistry();
    $streams = OutputStreams::create();

    $caches->add(new FakeCompiledCache('config', 'Configuration cache'));

    $exitCode = new CacheRebuildCommand($caches)->handle(
        new Input(['lpwork', 'cache:rebuild', '--only=views']),
        new Output($streams->stdout, $streams->stderr, decorated: false),
    );

    expect($exitCode)->toBe(1)
        ->and($streams->stderr())->toContain('ERROR The --only option must be one of: config.');
});
