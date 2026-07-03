<?php

declare(strict_types=1);

use LPWork\Cache\CacheClearer;
use LPWork\Cache\CacheManager;
use Tests\support\CacheTestFiles;

it('clears all configured cache stores by default', function (): void {
    $basePath = CacheTestFiles::createDirectory();

    try {
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
        ], $basePath);

        $manager->store('framework')->put('framework-key', 'cached');
        $manager->store('views')->put('view-key', 'cached');

        $cleared = new CacheClearer($manager)->clear();

        expect($cleared)->toBe(['framework', 'views'])
            ->and($manager->store('framework')->get('framework-key', 'missing'))->toBe('missing')
            ->and($manager->store('views')->get('view-key', 'missing'))->toBe('missing');
    } finally {
        CacheTestFiles::removeDirectory($basePath);
    }
});

it('clears a selected cache store', function (): void {
    $basePath = CacheTestFiles::createDirectory();

    try {
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
        ], $basePath);

        $manager->store('framework')->put('framework-key', 'cached');
        $manager->store('views')->put('view-key', 'cached');

        $cleared = new CacheClearer($manager)->clear('views');

        expect($cleared)->toBe(['views'])
            ->and($manager->store('framework')->get('framework-key'))->toBe('cached')
            ->and($manager->store('views')->get('view-key', 'missing'))->toBe('missing');
    } finally {
        CacheTestFiles::removeDirectory($basePath);
    }
});
