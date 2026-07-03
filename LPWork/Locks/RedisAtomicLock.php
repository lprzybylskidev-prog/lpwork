<?php

declare(strict_types=1);

namespace LPWork\Locks;

use LPWork\Locks\Contracts\AtomicLock;
use LPWork\Shared\Redis\RedisClient;

/**
 * Represents the redis atomic lock framework component.
 */
final readonly class RedisAtomicLock implements AtomicLock
{
    /**
     * Creates a new RedisAtomicLock instance.
     */
    public function __construct(
        private RedisClient $redis,
        private string $name,
        private string $owner,
        private int $ttlSeconds,
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Performs the owner operation.
     */
    public function owner(): string
    {
        return $this->owner;
    }

    /**
     * Performs the acquire operation.
     */
    public function acquire(): bool
    {
        return $this->redis->add('locks:' . $this->name, $this->owner, $this->ttlSeconds);
    }

    /**
     * Removes or clears release.
     */
    public function release(): bool
    {
        return $this->redis->deleteIfValue('locks:' . $this->name, $this->owner);
    }
}
