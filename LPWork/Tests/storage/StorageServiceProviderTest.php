<?php

declare(strict_types=1);

use LPWork\Config\Config;
use LPWork\Container\Container;
use LPWork\Filesystem\Filesystem;
use LPWork\Foundation\Application;
use LPWork\Foundation\Contracts\ServiceProvider;
use LPWork\Storage\Providers\StorageServiceProvider;
use LPWork\Storage\StorageDisk;
use LPWork\Storage\StorageManager;
use Tests\support\config\ApplicationConfigDefinitions;

beforeEach(function (): void {
    Config::reset();
});

afterEach(function (): void {
    Config::reset();
});

it('defines the storage service provider', function (): void {
    $provider = new StorageServiceProvider();

    expect($provider)->toBeInstanceOf(ServiceProvider::class);
});

it('registers storage services as singletons', function (): void {
    $container = new Container();
    $app = new Application(\Tests\support\ProjectPaths::root());
    $container->instance(Application::class, $app);
    ApplicationConfigDefinitions::initStorage();

    new StorageServiceProvider()->register($container);

    expect($container->make(Filesystem::class))
        ->toBeInstanceOf(Filesystem::class)
        ->toBe($container->make(Filesystem::class))
        ->and($container->make(StorageManager::class))
        ->toBeInstanceOf(StorageManager::class)
        ->toBe($container->make(StorageManager::class))
        ->and($container->make(StorageDisk::class))
        ->toBeInstanceOf(StorageDisk::class)
        ->toBe($container->make(StorageDisk::class));
});
