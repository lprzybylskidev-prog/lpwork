<?php

declare(strict_types=1);

use LPWork\Config\Config;
use LPWork\Container\Container;
use LPWork\Environment\Environment;
use LPWork\Foundation\Application;
use LPWork\Foundation\Providers\FoundationServiceProvider;
use LPWork\Routing\Router;
use Tests\support\testing\ApplicationTestHarness;

beforeEach(function (): void {
    ApplicationTestHarness::resetFrameworkState();
});

afterEach(function (): void {
    ApplicationTestHarness::resetFrameworkState();
});

afterAll(function (): void {
    ApplicationTestHarness::removeDirectories();
});

it('creates an isolated application with an explicit base path', function (): void {
    $harness = ApplicationTestHarness::create();

    $app = $harness->application();

    expect($app)
        ->toBeInstanceOf(Application::class)
        ->and($app->basePath())->toBe($harness->basePath())
        ->and($harness->container())->toBeInstanceOf(Container::class);
});

it('registers providers and overrides container entries explicitly', function (): void {
    $harness = ApplicationTestHarness::create();
    $service = new stdClass();

    $harness
        ->register(new FoundationServiceProvider($harness->application()))
        ->instance(stdClass::class, $service);

    expect($harness->container()->make(Application::class))->toBe($harness->application())
        ->and($harness->container()->make(stdClass::class))->toBe($service);
});

it('writes environment configuration cache and custom files under the test application base path', function (): void {
    $harness = ApplicationTestHarness::create()
        ->writeEnv(['APP_ENV' => 'testing'])
        ->writeConfig('feature.php', ['enabled' => true])
        ->writeFile('storage/testing/feature.txt', 'ready');

    expect($harness->envPath())->toBeFile()
        ->and($harness->configPath('feature.php'))->toBeFile()
        ->and($harness->basePath('storage/testing/feature.txt'))->toBeFile();
});

it('boots a project-default application through the real bootstrap lifecycle', function (): void {
    $harness = ApplicationTestHarness::fromProjectDefaults();

    $app = $harness->bootstrap();

    expect($app)->toBeInstanceOf(Application::class)
        ->and($app->basePath())->toBe($harness->basePath())
        ->and(Config::getString('app.url'))->toBe('http://localhost')
        ->and($app->container()->make(Router::class))->toBeInstanceOf(Router::class);
});

it('resets framework singleton lifecycle before bootstrapping', function (): void {
    $first = ApplicationTestHarness::fromProjectDefaults();
    $second = ApplicationTestHarness::fromProjectDefaults();

    $first->setEnvValue('APP_URL', 'http://first.test')->bootstrap();
    expect(Config::getString('app.url'))->toBe('http://first.test');

    $second->setEnvValue('APP_URL', 'http://second.test')->bootstrap();

    expect(Config::getString('app.url'))->toBe('http://second.test')
        ->and(Environment::getString('APP_URL'))->toBe('http://second.test');
});
