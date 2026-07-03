<?php

declare(strict_types=1);

use LPWork\Cache\CacheClearer;
use LPWork\Cache\CacheManager;
use LPWork\Cache\CacheStore;
use LPWork\Cache\Providers\CacheServiceProvider;
use LPWork\Config\Config;
use LPWork\Container\Container;
use LPWork\Database\Migrations\MigrationRegistry;
use LPWork\Foundation\Application;
use LPWork\Foundation\Contracts\ServiceProvider;
use LPWork\Storage\Providers\StorageServiceProvider;
use Tests\support\config\ApplicationConfigDefinitions;

beforeEach(function (): void {
    Config::reset();
});

afterEach(function (): void {
    Config::reset();
});

it('defines the cache service provider', function (): void {
    $provider = new CacheServiceProvider();

    expect($provider)->toBeInstanceOf(ServiceProvider::class);
});

it('registers cache services as singletons', function (): void {
    $container = new Container();
    $app = new Application(\Tests\support\ProjectPaths::root());
    $container->instance(Application::class, $app);
    ApplicationConfigDefinitions::initStorageAndCache();

    new StorageServiceProvider()->register($container);
    new CacheServiceProvider()->register($container);

    expect($container->make(CacheManager::class))
        ->toBeInstanceOf(CacheManager::class)
        ->toBe($container->make(CacheManager::class))
        ->and($container->make(CacheStore::class))
        ->toBeInstanceOf(CacheStore::class)
        ->toBe($container->make(CacheStore::class))
        ->and($container->make(CacheClearer::class))
        ->toBeInstanceOf(CacheClearer::class)
        ->toBe($container->make(CacheClearer::class));
});

it('does not register cache database migrations for the default file stores', function (): void {
    $container = new Container();
    $app = new Application(\Tests\support\ProjectPaths::root());
    $container->instance(Application::class, $app);
    $container->singleton(MigrationRegistry::class);
    ApplicationConfigDefinitions::initStorageAndCache();

    new StorageServiceProvider()->register($container);
    new CacheServiceProvider()->register($container);
    $registry = $container->make(MigrationRegistry::class);

    expect($registry)->toBeInstanceOf(MigrationRegistry::class);

    if (!$registry instanceof MigrationRegistry) {
        return;
    }

    expect($registry->connectionNames())->toBe([]);
});
