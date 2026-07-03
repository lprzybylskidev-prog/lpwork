<?php

declare(strict_types=1);

namespace Tests\support\locks;

use LPWork\Cache\CacheStore;
use LPWork\Cache\Drivers\FileCacheDriver;
use LPWork\Locks\AtomicLockManager;
use LPWork\Locks\CacheLockStore;
use Tests\support\cache\MutableCacheClock;

final class LockTestFactory
{
    public static function manager(string $basePath, ?MutableCacheClock $clock = null): AtomicLockManager
    {
        return new AtomicLockManager(
            new CacheLockStore(new CacheStore('locks', new FileCacheDriver('cache', $basePath, clock: $clock ?? new MutableCacheClock()))),
            60,
        );
    }
}
