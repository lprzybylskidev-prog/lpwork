<?php

declare(strict_types=1);

use LPWork\Cache\Providers\CacheServiceProvider;
use LPWork\Config\Config;
use LPWork\Config\Contracts\ConfigSource;
use LPWork\Container\Container;
use LPWork\Database\Migrations\MigrationRegistry;
use LPWork\Foundation\Application;
use LPWork\Locks\AtomicLockManager;
use LPWork\Locks\Contracts\LockStore;
use LPWork\Locks\Providers\LockServiceProvider;
use LPWork\Storage\Providers\StorageServiceProvider;

beforeEach(function (): void {
    Config::reset();
});

afterEach(function (): void {
    Config::reset();
});

it('registers lock services from configuration', function (): void {
    Config::initSource(new class implements ConfigSource {
        public function load(): array
        {
            return [
                'storage' => [
                    'default' => 'local',
                    'disks' => [
                        'local' => [
                            'driver' => 'local',
                            'root' => 'storage',
                        ],
                    ],
                ],
                'cache' => [
                    'default' => 'framework',
                    'stores' => [
                        'framework' => [
                            'driver' => 'file',
                            'disk' => 'local',
                            'path' => 'framework/cache',
                        ],
                    ],
                ],
                'locks' => [
                    'store' => 'framework',
                    'ttl_seconds' => 60,
                ],
            ];
        }
    });

    $container = new Container();
    $container->instance(Application::class, new Application(\Tests\support\ProjectPaths::root(), $container));
    new StorageServiceProvider()->register($container);
    new CacheServiceProvider()->register($container);
    new LockServiceProvider()->register($container);

    expect($container->make(LockStore::class))->toBeInstanceOf(LockStore::class)
        ->and($container->make(AtomicLockManager::class))->toBeInstanceOf(AtomicLockManager::class);
});

it('does not register lock database migrations for the default cache driver', function (): void {
    Config::initSource(new class implements ConfigSource {
        public function load(): array
        {
            return [
                'storage' => [
                    'default' => 'local',
                    'disks' => [
                        'local' => [
                            'driver' => 'local',
                            'root' => 'storage',
                        ],
                    ],
                ],
                'cache' => [
                    'default' => 'framework',
                    'stores' => [
                        'framework' => [
                            'driver' => 'file',
                            'disk' => 'local',
                            'path' => 'framework/cache',
                        ],
                    ],
                ],
                'locks' => [
                    'store' => 'framework',
                    'ttl_seconds' => 60,
                ],
            ];
        }
    });

    $container = new Container();
    $container->instance(Application::class, new Application(\Tests\support\ProjectPaths::root(), $container));
    $container->singleton(MigrationRegistry::class);
    new StorageServiceProvider()->register($container);
    new CacheServiceProvider()->register($container);
    new LockServiceProvider()->register($container);
    $registry = $container->make(MigrationRegistry::class);

    expect($registry)->toBeInstanceOf(MigrationRegistry::class);

    if (!$registry instanceof MigrationRegistry) {
        return;
    }

    expect($registry->connectionNames())->toBe([]);
});
