<?php

declare(strict_types=1);

use LPWork\Cache\CacheStore;
use LPWork\Locks\CacheLockStore;
use Tests\support\testing\Cache\InMemoryCacheDriver;
use Tests\support\testing\Locks\LockStoreContract;

it('keeps the cache lock store compatible with the shared lock store contract', function (): void {
    $store = new CacheLockStore(new CacheStore('locks', new InMemoryCacheDriver()));

    new LockStoreContract($store)->verifiesAtomicLockOwnership();
});
