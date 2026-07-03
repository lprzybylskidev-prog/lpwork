<?php

declare(strict_types=1);

use App\Shared\Configs\ConfigsProvider;
use LPWork\Config\Config;
use LPWork\Environment\Environment;
use LPWork\Foundation\Application;
use LPWork\Foundation\Contracts\ServiceProvider;
use LPWork\Throttle\CliThrottle;
use LPWork\Throttle\Contracts\ThrottleStorage;
use LPWork\Throttle\Exceptions\UnsupportedThrottleStorageException;
use LPWork\Throttle\Providers\ThrottleServiceProvider;
use LPWork\Throttle\ThrottleConfig;
use LPWork\Throttle\ThrottleConfigFactory;
use LPWork\Throttle\ThrottleLimiter;
use Tests\support\ApplicationTestEnvironment;

beforeEach(function (): void {
    Environment::reset();
    Config::reset();
});

afterEach(function (): void {
    Environment::reset();
    Config::reset();
});

afterAll(function (): void {
    ApplicationTestEnvironment::removeDirectories();
});

it('defines the throttle service provider', function (): void {
    expect(new ThrottleServiceProvider())->toBeInstanceOf(ServiceProvider::class);
});

it('registers throttle services from configuration', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $app = new Application($environment->basePath());

    Environment::init($environment->envPath());
    $app->register(new ConfigsProvider($app));
    $app->register(new ThrottleServiceProvider());

    expect($app->container()->make(ThrottleConfigFactory::class))->toBeInstanceOf(ThrottleConfigFactory::class)
        ->and($app->container()->make(ThrottleConfig::class))->toBeInstanceOf(ThrottleConfig::class)
        ->and($app->container()->make(ThrottleStorage::class))->toBeInstanceOf(ThrottleStorage::class)
        ->and($app->container()->make(ThrottleLimiter::class))->toBeInstanceOf(ThrottleLimiter::class)
        ->and($app->container()->make(CliThrottle::class))->toBeInstanceOf(CliThrottle::class);
});

it('rejects unsupported throttle storage drivers explicitly', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->setEnvValue('THROTTLE_STORAGE', 'missing');
    $app = new Application($environment->basePath());

    Environment::init($environment->envPath());
    $app->register(new ConfigsProvider($app));
    $app->register(new ThrottleServiceProvider());

    expect(fn() => $app->container()->make(ThrottleStorage::class))
        ->toThrow(UnsupportedThrottleStorageException::class, 'Throttle storage is not supported: missing.');
});
