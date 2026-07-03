<?php

declare(strict_types=1);

use LPWork\Cache\CacheStore;
use LPWork\Throttle\Storage\CacheThrottleStorage;
use LPWork\Throttle\Storage\InMemoryThrottleStorage;
use Tests\support\testing\Cache\InMemoryCacheDriver;
use Tests\support\testing\Throttle\ThrottleStorageContract;

it('keeps the in-memory throttle storage compatible with the shared throttle storage contract', function (): void {
    new ThrottleStorageContract(new InMemoryThrottleStorage())->verifiesHitCountersAndDecayWindows();
});

it('keeps the cache throttle storage compatible with the shared throttle storage contract', function (): void {
    $storage = new CacheThrottleStorage(new CacheStore('throttle', new InMemoryCacheDriver()));

    new ThrottleStorageContract($storage)->verifiesHitCountersAndDecayWindows();
});
