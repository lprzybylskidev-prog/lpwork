<?php

declare(strict_types=1);

use LPWork\Cache\CacheDriverFactory;
use LPWork\Cache\CacheManager;
use LPWork\Cache\CacheStore;
use LPWork\Cache\Exceptions\InvalidCacheConfigException;
use LPWork\Cache\Exceptions\InvalidCacheDriverException;
use LPWork\Cache\Exceptions\InvalidCacheStoreException;
use LPWork\Cache\Exceptions\MissingCacheConfigException;
use LPWork\Storage\StorageManager;

it('returns the configured default cache store', function (): void {
    $manager = new CacheManager([
        'default' => 'framework',
        'stores' => [
            'framework' => [
                'driver' => 'file',
                'path' => 'storage/framework/cache',
            ],
        ],
    ], \Tests\support\ProjectPaths::root());

    expect($manager->default())->toBeInstanceOf(CacheStore::class)
        ->and($manager->default()->name)->toBe('framework');
});

it('returns cache stores by name', function (): void {
    $manager = new CacheManager([
        'default' => 'framework',
        'stores' => [
            'framework' => [
                'driver' => 'file',
                'path' => 'storage/framework/cache',
            ],
            'views' => [
                'driver' => 'file',
                'path' => 'storage/framework/views',
            ],
        ],
    ], \Tests\support\ProjectPaths::root());

    expect($manager->store('views'))->toBeInstanceOf(CacheStore::class)
        ->and($manager->store('views')->name)->toBe('views');
});

it('returns configured cache store names', function (): void {
    $manager = new CacheManager([
        'default' => 'framework',
        'stores' => [
            'framework' => [
                'driver' => 'file',
                'path' => 'storage/framework/cache',
            ],
            'views' => [
                'driver' => 'file',
                'path' => 'storage/framework/views',
            ],
        ],
    ], \Tests\support\ProjectPaths::root());

    expect($manager->storeNames())->toBe(['framework', 'views']);
});

it('caches created cache stores', function (): void {
    $manager = new CacheManager([
        'default' => 'framework',
        'stores' => [
            'framework' => [
                'driver' => 'file',
                'path' => 'storage/framework/cache',
            ],
        ],
    ], \Tests\support\ProjectPaths::root());

    expect($manager->store('framework'))->toBe($manager->store('framework'));
});

it('uses configured storage disks for file cache stores', function (): void {
    $storage = new StorageManager([
        'default' => 'memory',
        'disks' => [
            'memory' => [
                'driver' => 'memory',
            ],
        ],
    ], \Tests\support\ProjectPaths::root());
    $manager = new CacheManager([
        'default' => 'framework',
        'stores' => [
            'framework' => [
                'driver' => 'file',
                'disk' => 'memory',
                'path' => 'framework/cache',
            ],
        ],
    ], \Tests\support\ProjectPaths::root(), new CacheDriverFactory(\Tests\support\ProjectPaths::root(), $storage));

    $manager->default()->put('storage-backed', 'cached');

    expect($manager->default()->get('storage-backed'))->toBe('cached');

    $manager->default()->clear();

    expect($manager->default()->get('storage-backed', 'missing'))->toBe('missing');
});

it('throws when cache config is missing', function (): void {
    expect(fn() => new CacheManager([], \Tests\support\ProjectPaths::root())->default())
        ->toThrow(MissingCacheConfigException::class);
});

it('throws when cache config is invalid', function (): void {
    expect(fn() => new CacheManager(['default' => '', 'stores' => []], \Tests\support\ProjectPaths::root())->default())
        ->toThrow(InvalidCacheConfigException::class);
});

it('throws when cache store is not configured', function (): void {
    $manager = new CacheManager([
        'default' => 'framework',
        'stores' => [],
    ], \Tests\support\ProjectPaths::root());

    expect(fn(): CacheStore => $manager->store('missing'))
        ->toThrow(InvalidCacheStoreException::class);
});

it('throws when configured driver type is unsupported', function (): void {
    $manager = new CacheManager([
        'default' => 'framework',
        'stores' => [
            'framework' => [
                'driver' => 'missing',
            ],
        ],
    ], \Tests\support\ProjectPaths::root());

    expect(fn(): CacheStore => $manager->default())
        ->toThrow(InvalidCacheDriverException::class, 'Cache driver is not supported: missing.');
});

it('validates redis cache driver configuration', function (): void {
    $manager = new CacheManager([
        'default' => 'framework',
        'stores' => [
            'framework' => [
                'driver' => 'redis',
            ],
        ],
    ], \Tests\support\ProjectPaths::root());

    expect(fn(): CacheStore => $manager->default())
        ->toThrow(MissingCacheConfigException::class, 'Missing cache configuration value: stores.framework.host.');
});

it('throws when file cache driver config is incomplete', function (): void {
    $manager = new CacheManager([
        'default' => 'framework',
        'stores' => [
            'framework' => [
                'driver' => 'file',
            ],
        ],
    ], \Tests\support\ProjectPaths::root());

    expect(fn(): CacheStore => $manager->default())
        ->toThrow(MissingCacheConfigException::class);
});
