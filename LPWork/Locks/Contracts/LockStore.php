<?php

declare(strict_types=1);

namespace LPWork\Locks\Contracts;

/**
 * Defines the contract for lock store.
 */
interface LockStore
{
    /**
     * Performs the lock operation.
     */
    public function lock(string $name, int $ttlSeconds): AtomicLock;
}
