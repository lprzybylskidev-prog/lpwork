<?php

declare(strict_types=1);

use LPWork\Cache\Drivers\DatabaseCacheDriver;
use LPWork\Cache\Drivers\FileCacheDriver;
use LPWork\Cache\Migrations\CreateCacheEntriesTable;
use LPWork\Database\DatabaseManager;
use Tests\support\cache\MutableCacheClock;
use Tests\support\CacheTestFiles;
use Tests\support\database\SqliteDatabase;
use Tests\support\testing\Cache\CacheDriverContract;

it('keeps the file cache driver compatible with the shared cache contract', function (): void {
    $basePath = CacheTestFiles::createDirectory();

    try {
        $clock = new MutableCacheClock();
        $contract = new CacheDriverContract(
            new FileCacheDriver('storage/cache', $basePath, clock: $clock),
            $clock->travel(...),
        );

        $contract->verifiesCoreCacheBehavior();
        $contract->verifiesExpiringCacheValues();
    } finally {
        CacheTestFiles::removeDirectory($basePath);
    }
});

it('keeps the database cache driver compatible with the shared cache contract', function (): void {
    $database = SqliteDatabase::create();

    try {
        $clock = new MutableCacheClock();
        $connection = new DatabaseManager([
            'default' => 'sqlite',
            'connections' => [
                'sqlite' => [
                    'driver' => 'sqlite',
                    'database' => $database->relativePath(),
                ],
            ],
        ], $database->basePath())->default();
        new CreateCacheEntriesTable('cache_entries')->up($connection);

        $contract = new CacheDriverContract(
            new DatabaseCacheDriver($connection, clock: $clock),
            $clock->travel(...),
        );

        $contract->verifiesCoreCacheBehavior();
        $contract->verifiesExpiringCacheValues();
    } finally {
        $database->remove();
    }
});
