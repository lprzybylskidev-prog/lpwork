<?php

declare(strict_types=1);

use LPWork\Filesystem\Exceptions\FileNotFoundException;
use LPWork\Filesystem\Exceptions\InvalidPathException;
use LPWork\Storage\Exceptions\InvalidStorageConfigException;
use LPWork\Storage\Exceptions\InvalidStorageDiskException;
use LPWork\Storage\Exceptions\InvalidStorageDriverException;
use LPWork\Storage\Exceptions\MissingStorageConfigException;
use LPWork\Storage\Exceptions\StorageFileNotFoundException;
use LPWork\Storage\Exceptions\StorageUrlNotConfiguredException;
use LPWork\Storage\StorageManager;
use Tests\support\testing\Filesystem\TestFilesystem;

it('returns and caches the configured default storage disk', function (): void {
    $files = TestFilesystem::create();

    try {
        $manager = new StorageManager([
            'default' => 'local',
            'disks' => [
                'local' => [
                    'driver' => 'local',
                    'root' => 'storage',
                ],
            ],
        ], $files->root());

        expect($manager->default())->toBe($manager->disk('local'))
            ->and($manager->diskNames())->toBe(['local']);
    } finally {
        $files->cleanup();
    }
});

it('writes and reads files through local storage disks', function (): void {
    $files = TestFilesystem::create();

    try {
        $manager = new StorageManager([
            'default' => 'local',
            'disks' => [
                'local' => [
                    'driver' => 'local',
                    'root' => 'storage',
                ],
            ],
        ], $files->root());

        $disk = $manager->disk('local');
        $disk->put('cache/item.bin', "abc\0def");
        $disk->append('cache/item.bin', 'ghi');

        expect($disk->exists('cache/item.bin'))->toBeTrue()
            ->and($disk->get('cache/item.bin'))->toBe("abc\0defghi");
    } finally {
        $files->cleanup();
    }
});

it('deletes and clears files through local storage disks', function (): void {
    $files = TestFilesystem::create();

    try {
        $manager = new StorageManager([
            'default' => 'local',
            'disks' => [
                'local' => [
                    'driver' => 'local',
                    'root' => 'storage',
                ],
            ],
        ], $files->root());

        $disk = $manager->disk('local');
        $disk->put('cache/first.bin', 'first');
        $disk->put('cache/nested/second.bin', 'second');
        $disk->delete('cache/first.bin');
        $disk->delete('cache/first.bin');

        expect($disk->exists('cache/first.bin'))->toBeFalse()
            ->and($disk->exists('cache/nested/second.bin'))->toBeTrue();

        $disk->clear('cache');

        expect($disk->exists('cache/nested/second.bin'))->toBeFalse();
    } finally {
        $files->cleanup();
    }
});

it('throws when a local storage file is missing', function (): void {
    $files = TestFilesystem::create();

    try {
        $manager = new StorageManager([
            'default' => 'local',
            'disks' => [
                'local' => [
                    'driver' => 'local',
                    'root' => 'storage',
                ],
            ],
        ], $files->root());

        expect(fn(): string => $manager->disk('local')->get('missing.bin'))
            ->toThrow(FileNotFoundException::class);
    } finally {
        $files->cleanup();
    }
});

it('writes and reads files through memory storage disks', function (): void {
    $manager = new StorageManager([
        'default' => 'memory',
        'disks' => [
            'memory' => [
                'driver' => 'memory',
            ],
        ],
    ], \Tests\support\ProjectPaths::root());

    $disk = $manager->default();
    $disk->put('tmp/value.txt', 'stored');
    $disk->append('tmp/value.txt', ' again');

    expect($disk->get('tmp/value.txt'))->toBe('stored again');
});

it('deletes and clears files through memory storage disks', function (): void {
    $manager = new StorageManager([
        'default' => 'memory',
        'disks' => [
            'memory' => [
                'driver' => 'memory',
            ],
        ],
    ], \Tests\support\ProjectPaths::root());

    $disk = $manager->default();
    $disk->put('tmp/first.txt', 'first');
    $disk->put('tmp/nested/second.txt', 'second');
    $disk->delete('tmp/first.txt');
    $disk->delete('tmp/first.txt');

    expect($disk->exists('tmp/first.txt'))->toBeFalse()
        ->and($disk->exists('tmp/nested/second.txt'))->toBeTrue();

    $disk->clear('tmp');

    expect($disk->exists('tmp/nested/second.txt'))->toBeFalse();
});

it('keeps memory storage disk instances isolated', function (): void {
    $manager = new StorageManager([
        'default' => 'first',
        'disks' => [
            'first' => [
                'driver' => 'memory',
            ],
            'second' => [
                'driver' => 'memory',
            ],
        ],
    ], \Tests\support\ProjectPaths::root());

    $manager->disk('first')->put('tmp/value.txt', 'stored');

    expect($manager->disk('second')->exists('tmp/value.txt'))->toBeFalse();
});

it('throws when a memory storage file is missing', function (): void {
    $manager = new StorageManager([
        'default' => 'memory',
        'disks' => [
            'memory' => [
                'driver' => 'memory',
            ],
        ],
    ], \Tests\support\ProjectPaths::root());

    expect(fn(): string => $manager->disk('memory')->get('missing.bin'))
        ->toThrow(StorageFileNotFoundException::class);
});

it('generates public URLs from disk configuration', function (): void {
    $manager = new StorageManager([
        'default' => 'public',
        'disks' => [
            'public' => [
                'driver' => 'memory',
                'url' => '/storage',
            ],
            'cdn' => [
                'driver' => 'memory',
                'url' => 'https://cdn.example.test/assets/',
            ],
        ],
    ], \Tests\support\ProjectPaths::root());

    expect($manager->disk('public')->url('avatars/me.png'))->toBe('/storage/avatars/me.png')
        ->and($manager->url('avatars/me.png'))->toBe('/storage/avatars/me.png')
        ->and($manager->url('avatars/me.png', 'cdn'))->toBe('https://cdn.example.test/assets/avatars/me.png');
});

it('throws when generating URLs for disks without a public URL', function (): void {
    $manager = new StorageManager([
        'default' => 'local',
        'disks' => [
            'local' => [
                'driver' => 'memory',
            ],
        ],
    ], \Tests\support\ProjectPaths::root());

    expect(fn(): string => $manager->disk('local')->url('avatars/me.png'))
        ->toThrow(StorageUrlNotConfiguredException::class);
});

it('rejects unsafe storage paths', function (): void {
    $manager = new StorageManager([
        'default' => 'memory',
        'disks' => [
            'memory' => [
                'driver' => 'memory',
            ],
        ],
    ], \Tests\support\ProjectPaths::root());

    expect(fn() => $manager->disk('memory')->put('../escape.txt', 'unsafe'))
        ->toThrow(InvalidPathException::class);
});

it('rejects unsafe storage URL paths', function (): void {
    $manager = new StorageManager([
        'default' => 'public',
        'disks' => [
            'public' => [
                'driver' => 'memory',
                'url' => '/storage',
            ],
        ],
    ], \Tests\support\ProjectPaths::root());

    expect(fn(): string => $manager->url('../escape.txt'))
        ->toThrow(InvalidPathException::class);
});

it('throws when the configured default storage disk is missing', function (): void {
    expect(fn() => new StorageManager([], \Tests\support\ProjectPaths::root())->default())
        ->toThrow(MissingStorageConfigException::class);
});

it('throws when storage config is invalid', function (): void {
    expect(fn() => new StorageManager(['default' => '', 'disks' => []], \Tests\support\ProjectPaths::root())->default())
        ->toThrow(InvalidStorageConfigException::class);
});

it('throws when a storage disk is not configured', function (): void {
    $manager = new StorageManager([
        'default' => 'local',
        'disks' => [],
    ], \Tests\support\ProjectPaths::root());

    expect(fn() => $manager->disk('missing'))
        ->toThrow(InvalidStorageDiskException::class);
});

it('throws when a storage driver is unsupported', function (): void {
    $manager = new StorageManager([
        'default' => 'remote',
        'disks' => [
            'remote' => [
                'driver' => 'missing',
            ],
        ],
    ], \Tests\support\ProjectPaths::root());

    expect(fn() => $manager->default())
        ->toThrow(InvalidStorageDriverException::class, 'Storage driver is not supported: missing.');
});

it('validates s3 storage driver configuration', function (): void {
    $manager = new StorageManager([
        'default' => 'remote',
        'disks' => [
            'remote' => [
                'driver' => 's3',
            ],
        ],
    ], \Tests\support\ProjectPaths::root());

    expect(fn() => $manager->default())
        ->toThrow(MissingStorageConfigException::class, 'Storage configuration value is missing: disks.remote.bucket.');
});

it('throws when local storage driver config is incomplete', function (): void {
    $manager = new StorageManager([
        'default' => 'local',
        'disks' => [
            'local' => [
                'driver' => 'local',
            ],
        ],
    ], \Tests\support\ProjectPaths::root());

    expect(fn() => $manager->default())
        ->toThrow(MissingStorageConfigException::class);
});
