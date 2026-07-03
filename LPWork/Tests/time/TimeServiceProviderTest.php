<?php

declare(strict_types=1);

use App\Shared\Configs\ConfigsProvider;
use LPWork\Config\Config;
use LPWork\Environment\Environment;
use LPWork\Foundation\Application;
use LPWork\Foundation\Contracts\ServiceProvider;
use LPWork\Time\Contracts\Clock;
use LPWork\Time\Exceptions\InvalidTimezoneException;
use LPWork\Time\Providers\TimeServiceProvider;
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

it('defines the time service provider', function (): void {
    expect(new TimeServiceProvider())->toBeInstanceOf(ServiceProvider::class);
});

it('creates application time with the configured timezone', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->setEnvValue('APP_TIMEZONE', 'Europe/Warsaw');
    $app = new Application($environment->basePath());

    Environment::init($environment->envPath());
    $app->register(new ConfigsProvider($app));
    $app->register(new TimeServiceProvider());

    $clock = $app->container()->make(Clock::class);

    expect($clock)->toBeInstanceOf(Clock::class);

    if (!$clock instanceof Clock) {
        return;
    }

    expect($clock->now()->getTimezone()->getName())->toBe('Europe/Warsaw');
});

it('rejects invalid application timezones explicitly', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->setEnvValue('APP_TIMEZONE', 'Missing/Timezone');
    $app = new Application($environment->basePath());

    Environment::init($environment->envPath());
    $app->register(new ConfigsProvider($app));
    $app->register(new TimeServiceProvider());

    expect(fn() => $app->container()->make(Clock::class))
        ->toThrow(InvalidTimezoneException::class, 'Application timezone is invalid: Missing/Timezone.');
});
