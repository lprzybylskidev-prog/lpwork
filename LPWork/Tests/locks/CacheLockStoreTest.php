<?php

declare(strict_types=1);

use Tests\support\cache\MutableCacheClock;
use Tests\support\CacheTestFiles;
use Tests\support\locks\LockTestFactory;

it('acquires only one live lock for a name', function (): void {
    $basePath = CacheTestFiles::createDirectory();

    try {
        $manager = LockTestFactory::manager($basePath);
        $first = $manager->lock('schedule:task', 60);
        $second = $manager->lock('schedule:task', 60);

        expect($first->acquire())->toBeTrue()
            ->and($second->acquire())->toBeFalse();
    } finally {
        CacheTestFiles::removeDirectory($basePath);
    }
});

it('releases only the owner that acquired the lock', function (): void {
    $basePath = CacheTestFiles::createDirectory();

    try {
        $manager = LockTestFactory::manager($basePath);
        $first = $manager->lock('schedule:task', 60);
        $second = $manager->lock('schedule:task', 60);

        $first->acquire();

        expect($second->release())->toBeFalse()
            ->and($second->acquire())->toBeFalse()
            ->and($first->release())->toBeTrue()
            ->and($second->acquire())->toBeTrue();
    } finally {
        CacheTestFiles::removeDirectory($basePath);
    }
});

it('allows expired locks to be acquired again', function (): void {
    $basePath = CacheTestFiles::createDirectory();

    try {
        $clock = new MutableCacheClock();
        $manager = LockTestFactory::manager($basePath, $clock);
        $first = $manager->lock('schedule:task', 10);
        $second = $manager->lock('schedule:task', 10);

        $first->acquire();
        $clock->travel(11);

        expect($second->acquire())->toBeTrue();
    } finally {
        CacheTestFiles::removeDirectory($basePath);
    }
});
