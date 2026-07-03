<?php

declare(strict_types=1);

namespace LPWork\Locks;

use LPWork\Cache\CacheStore;
use LPWork\Locks\Contracts\AtomicLock;
use LPWork\Locks\Contracts\LockStore;

/**
 * Represents the cache lock store framework component.
 */
final readonly class CacheLockStore implements LockStore
{
    /**
     * Creates a new CacheLockStore instance.
     */
    public function __construct(
        private CacheStore $cache,
    ) {}

    /**
     * Performs the lock operation.
     */
    public function lock(string $name, int $ttlSeconds): AtomicLock
    {
        return new CacheAtomicLock(
            cache: $this->cache,
            name: $name,
            owner: bin2hex(random_bytes(16)),
            ttlSeconds: $ttlSeconds,
        );
    }
}
