<?php

declare(strict_types=1);

use LPWork\Cache\Drivers\FileCacheDriver;
use LPWork\Cache\Exceptions\InvalidCacheKeyException;
use LPWork\Cache\Exceptions\InvalidCacheTtlException;
use Tests\support\cache\MutableCacheClock;
use Tests\support\CacheTestFiles;

it('reads and writes cached values', function (): void {
    $basePath = CacheTestFiles::createDirectory();

    try {
        $driver = new FileCacheDriver('storage/cache', $basePath);

        $driver->put('user.profile', ['name' => 'Ada']);

        expect($driver->get('user.profile'))->toBe(['name' => 'Ada']);
    } finally {
        CacheTestFiles::removeDirectory($basePath);
    }
});

it('expires cached values written with ttl', function (): void {
    $basePath = CacheTestFiles::createDirectory();

    try {
        $clock = new MutableCacheClock();
        $driver = new FileCacheDriver('storage/cache', $basePath, clock: $clock);

        $driver->put('short-lived', 'cached', 10);
        $clock->travel(11);

        expect($driver->get('short-lived', 'missing'))->toBe('missing');
    } finally {
        CacheTestFiles::removeDirectory($basePath);
    }
});

it('adds cache values atomically without overwriting live keys', function (): void {
    $basePath = CacheTestFiles::createDirectory();

    try {
        $driver = new FileCacheDriver('storage/cache', $basePath);

        expect($driver->add('lock', 'first-owner', 60))->toBeTrue()
            ->and($driver->add('lock', 'second-owner', 60))->toBeFalse()
            ->and($driver->get('lock'))->toBe('first-owner');
    } finally {
        CacheTestFiles::removeDirectory($basePath);
    }
});

it('allows atomic add to replace expired keys', function (): void {
    $basePath = CacheTestFiles::createDirectory();

    try {
        $clock = new MutableCacheClock();
        $driver = new FileCacheDriver('storage/cache', $basePath, clock: $clock);

        $driver->add('lock', 'first-owner', 10);
        $clock->travel(11);

        expect($driver->add('lock', 'second-owner', 10))->toBeTrue()
            ->and($driver->get('lock'))->toBe('second-owner');
    } finally {
        CacheTestFiles::removeDirectory($basePath);
    }
});

it('rejects non-positive cache ttl values', function (): void {
    $basePath = CacheTestFiles::createDirectory();

    try {
        $driver = new FileCacheDriver('storage/cache', $basePath);

        expect(fn() => $driver->add('lock', 'owner', 0))
            ->toThrow(InvalidCacheTtlException::class);
    } finally {
        CacheTestFiles::removeDirectory($basePath);
    }
});

it('returns the provided default when a cache value is missing', function (): void {
    $basePath = CacheTestFiles::createDirectory();

    try {
        $driver = new FileCacheDriver('storage/cache', $basePath);

        expect($driver->get('missing', 'fallback'))->toBe('fallback');
    } finally {
        CacheTestFiles::removeDirectory($basePath);
    }
});

it('forgets cached values', function (): void {
    $basePath = CacheTestFiles::createDirectory();

    try {
        $driver = new FileCacheDriver('storage/cache', $basePath);

        $driver->put('session', 'cached');
        $driver->forget('session');

        expect($driver->get('session', 'missing'))->toBe('missing');
    } finally {
        CacheTestFiles::removeDirectory($basePath);
    }
});

it('forgets cached values only when the value matches', function (): void {
    $basePath = CacheTestFiles::createDirectory();

    try {
        $driver = new FileCacheDriver('storage/cache', $basePath);

        $driver->put('lock', 'first-owner');

        expect($driver->forgetIfValue('lock', 'second-owner'))->toBeFalse()
            ->and($driver->get('lock'))->toBe('first-owner')
            ->and($driver->forgetIfValue('lock', 'first-owner'))->toBeTrue()
            ->and($driver->get('lock', 'missing'))->toBe('missing');
    } finally {
        CacheTestFiles::removeDirectory($basePath);
    }
});

it('clears cached values', function (): void {
    $basePath = CacheTestFiles::createDirectory();

    try {
        $driver = new FileCacheDriver('storage/cache', $basePath);

        $driver->put('first', 'cached');
        $driver->put('second', 'also cached');
        $driver->clear();

        expect($driver->get('first', 'missing'))->toBe('missing')
            ->and($driver->get('second', 'missing'))->toBe('missing');
    } finally {
        CacheTestFiles::removeDirectory($basePath);
    }
});

it('rejects empty cache keys', function (): void {
    $basePath = CacheTestFiles::createDirectory();

    try {
        $driver = new FileCacheDriver('storage/cache', $basePath);

        expect(fn() => $driver->put('', 'cached'))
            ->toThrow(InvalidCacheKeyException::class);
    } finally {
        CacheTestFiles::removeDirectory($basePath);
    }
});
