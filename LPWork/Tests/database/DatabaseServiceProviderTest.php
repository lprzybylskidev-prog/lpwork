<?php

declare(strict_types=1);

use LPWork\Config\Config;
use LPWork\Database\Contracts\Connection;
use LPWork\Database\DatabaseDebugCollector;
use LPWork\Database\DatabaseManager;
use LPWork\Database\Providers\DatabaseServiceProvider;
use LPWork\Environment\Environment;
use LPWork\Foundation\Contracts\ServiceProvider;
use LPWork\Foundation\Providers\FoundationServiceProvider;
use LPWork\Logging\Providers\LoggingServiceProvider;
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

it('defines the database service provider', function (): void {
    expect(new DatabaseServiceProvider())->toBeInstanceOf(ServiceProvider::class);
});

it('registers the database manager and default connection in the container', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $app = ApplicationFactory::create($environment->basePath());

    $environment->setEnvValue('DB_SQLITE_DATABASE', ':memory:');
    Environment::init($environment->envPath());
    Config::initDefinitions([
        new App\Shared\Configs\AppConfig(),
        new App\Shared\Configs\CacheConfig(),
        new App\Shared\Configs\DatabaseConfig(),
        new App\Shared\Configs\LoggingConfig(),
        new App\Shared\Configs\StorageConfig(),
    ]);

    $app->register(new FoundationServiceProvider($app));
    $app->register(new StorageServiceProvider());
    $app->register(new LoggingServiceProvider());
    $app->register(new DatabaseServiceProvider());

    expect($app->container()->make(DatabaseManager::class))->toBeInstanceOf(DatabaseManager::class)
        ->and($app->container()->make(Connection::class))->toBeInstanceOf(Connection::class)
        ->and($app->container()->make(DatabaseDebugCollector::class))->toBeInstanceOf(DatabaseDebugCollector::class);
});
