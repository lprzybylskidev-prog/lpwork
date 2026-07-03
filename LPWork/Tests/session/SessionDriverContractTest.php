<?php

declare(strict_types=1);

use LPWork\Cache\CacheStore;
use LPWork\Session\Drivers\CacheSessionDriver;
use LPWork\Session\Drivers\InMemorySessionDriver;
use Tests\support\testing\Cache\InMemoryCacheDriver;
use Tests\support\testing\Session\SessionDriverContract;

it('keeps the in-memory session driver compatible with the shared session contract', function (): void {
    new SessionDriverContract(new InMemorySessionDriver())->verifiesSessionPersistence();
});

it('keeps the cache session driver compatible with the shared session contract', function (): void {
    $driver = new CacheSessionDriver(
        new CacheStore('sessions', new InMemoryCacheDriver()),
        name: 'LPWORK_SESSION',
        lifetime: 120,
    );

    new SessionDriverContract($driver)->verifiesSessionPersistence();
});
