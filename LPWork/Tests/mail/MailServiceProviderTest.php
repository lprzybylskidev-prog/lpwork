<?php

declare(strict_types=1);

use LPWork\Config\Config;
use LPWork\Environment\Environment;
use LPWork\Foundation\Contracts\ServiceProvider;
use LPWork\Foundation\Providers\FoundationServiceProvider;
use LPWork\Logging\Providers\LoggingServiceProvider;
use LPWork\Mail\Contracts\MailTransport;
use LPWork\Mail\MailManager;
use LPWork\Mail\Providers\MailServiceProvider;
use LPWork\Storage\Providers\StorageServiceProvider;
use Tests\support\ApplicationFactory;
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

it('defines the mail service provider', function (): void {
    expect(new MailServiceProvider())->toBeInstanceOf(ServiceProvider::class);
});

it('registers the mail manager and default transport in the container', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $app = ApplicationFactory::create($environment->basePath());

    Environment::init($environment->envPath());
    Config::initDefinitions([
        new App\Shared\Configs\AppConfig(),
        new App\Shared\Configs\CacheConfig(),
        new App\Shared\Configs\LoggingConfig(),
        new App\Shared\Configs\MailConfig(),
        new App\Shared\Configs\StorageConfig(),
    ]);

    $app->register(new FoundationServiceProvider($app));
    $app->register(new StorageServiceProvider());
    $app->register(new LoggingServiceProvider());
    $app->register(new MailServiceProvider());

    expect($app->container()->make(MailManager::class))
        ->toBeInstanceOf(MailManager::class)
        ->toBe($app->container()->make(MailManager::class))
        ->and($app->container()->make(MailTransport::class))
        ->toBeInstanceOf(MailTransport::class);
});
