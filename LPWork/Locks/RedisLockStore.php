<?php

declare(strict_types=1);

namespace LPWork\Locks;

use LPWork\Locks\Contracts\AtomicLock;
use LPWork\Locks\Contracts\LockStore;
use LPWork\Shared\Redis\RedisClient;

/**
 * Represents the redis lock store framework component.
 */
final readonly class RedisLockStore implements LockStore
{
    /**
     * Creates a new RedisLockStore instance.
     */
    public function __construct(private RedisClient $redis) {}

    /**
     * Performs the lock operation.
     */
    public function lock(string $name, int $ttlSeconds): AtomicLock
    {
        return new RedisAtomicLock($this->redis, $name, bin2hex(random_bytes(16)), $ttlSeconds);
    }
}
