<?php

declare(strict_types=1);

namespace LPWork\Locks;

use LPWork\Locks\Contracts\AtomicLock;
use LPWork\Locks\Contracts\LockStore;

/**
 * Coordinates configured atomic lock manager services.
 */
final readonly class AtomicLockManager
{
    /**
     * Creates a new AtomicLockManager instance.
     */
    public function __construct(
        private LockStore $store,
        private int $defaultTtlSeconds,
    ) {}

    /**
     * Performs the lock operation.
     */
    public function lock(string $name, ?int $ttlSeconds = null): AtomicLock
    {
        return $this->store->lock($name, $ttlSeconds ?? $this->defaultTtlSeconds);
    }
}
