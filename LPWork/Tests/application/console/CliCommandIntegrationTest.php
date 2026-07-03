<?php

declare(strict_types=1);

use LPWork\Config\Config;
use Tests\support\testing\ApplicationTestHarness;
use Tests\support\testing\Cli\CliTestClient;

beforeEach(function (): void {
    ApplicationTestHarness::resetFrameworkState();
});

afterEach(function (): void {
    ApplicationTestHarness::resetFrameworkState();
});

afterAll(function (): void {
    ApplicationTestHarness::removeDirectories();
});

it('runs the built-in route list command after bootstrap loads application routes', function (): void {
    $harness = ApplicationTestHarness::fromProjectDefaults();

    CliTestClient::forApplication($harness->bootstrap(['lpwork', 'route:list']))
        ->command('route:list')
        ->assertSuccessful()
        ->assertStdoutContains('Registered routes:')
        ->assertStdoutContains('/maintenance')
        ->assertStdoutContains('maintenance.show')
        ->assertStdoutContains('/error/{code}')
        ->assertStdoutContains('error.show')
        ->assertStdoutContains('App\\Modules\\Welcome\\Controllers\\HomeController@index')
        ->assertStdoutContains('home')
        ->assertNoStderr();
});

it('runs the built-in config show command after bootstrap loads configuration', function (): void {
    $harness = ApplicationTestHarness::fromProjectDefaults();

    CliTestClient::forApplication($harness->bootstrap(['lpwork', 'config:show', 'app.url']))
        ->command('config:show', 'app.url')
        ->assertSuccessful()
        ->assertStdoutContains('Configuration:')
        ->assertStdoutContains('app.url')
        ->assertNoStderr();
});

it('runs the built-in about command after bootstrap loads runtime services', function (): void {
    $harness = ApplicationTestHarness::fromProjectDefaults();

    CliTestClient::forApplication($harness->bootstrap(['lpwork', 'about']))
        ->command('about')
        ->assertSuccessful()
        ->assertStdoutContains('LPWork application information')
        ->assertStdoutContains('Environment')
        ->assertStdoutContains('Framework modules')
        ->assertNoStderr();
});

it('runs the built-in config cache command after bootstrap loads source configuration files', function (): void {
    $harness = ApplicationTestHarness::fromProjectDefaults();

    CliTestClient::forApplication($harness->bootstrap(['lpwork', 'config:cache']))
        ->command('config:cache')
        ->assertSuccessful()
        ->assertStdoutContains('Configuration cache rebuilt successfully.')
        ->assertNoStderr();

    expect($harness->basePath('storage/framework/cache/config.php'))->toBeFile();

    Config::reset();
    Config::initCached($harness->basePath('storage/framework/cache/config.php'));

    expect(Config::getString('welcome::app.name'))->toBe('Welcome')
        ->and(Config::getString('app.name'))->toBe('LPWork');
});

it('runs route and translation cache commands through application bootstrap', function (): void {
    $harness = ApplicationTestHarness::fromProjectDefaults();

    CliTestClient::forApplication($harness->bootstrap(['lpwork', 'route:cache']))
        ->command('route:cache')
        ->assertSuccessful()
        ->assertStdoutContains('Route cache rebuilt successfully.')
        ->assertNoStderr();

    expect($harness->basePath('storage/framework/cache/routes.php'))->toBeFile();

    CliTestClient::forApplication($harness->bootstrap(['lpwork', 'translation:cache']))
        ->command('translation:cache')
        ->assertSuccessful()
        ->assertStdoutContains('Translation cache rebuilt successfully.')
        ->assertNoStderr();

    expect($harness->basePath('storage/framework/cache/translations.php'))->toBeFile();
});

it('rebuilds all compiled framework caches through one command', function (): void {
    $harness = ApplicationTestHarness::fromProjectDefaults();

    CliTestClient::forApplication($harness->bootstrap(['lpwork', 'cache:rebuild']))
        ->command('cache:rebuild')
        ->assertSuccessful()
        ->assertStdoutContains('Rebuilding framework caches:')
        ->assertStdoutContains('Configuration cache rebuilt successfully.')
        ->assertStdoutContains('Route cache rebuilt successfully.')
        ->assertStdoutContains('Translation cache rebuilt successfully.')
        ->assertStdoutContains('Framework caches rebuilt.')
        ->assertNoStderr();

    expect($harness->basePath('storage/framework/cache/config.php'))->toBeFile()
        ->and($harness->basePath('storage/framework/cache/routes.php'))->toBeFile()
        ->and($harness->basePath('storage/framework/cache/translations.php'))->toBeFile();
});
