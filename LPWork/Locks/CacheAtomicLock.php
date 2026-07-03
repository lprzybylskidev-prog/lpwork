<?php

declare(strict_types=1);

namespace LPWork\Locks;

use LPWork\Cache\CacheStore;
use LPWork\Locks\Contracts\AtomicLock;

/**
 * Represents the cache atomic lock framework component.
 */
final readonly class CacheAtomicLock implements AtomicLock
{
    /**
     * Creates a new CacheAtomicLock instance.
     */
    public function __construct(
        private CacheStore $cache,
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
        return $this->cache->add($this->cacheKey(), $this->owner, $this->ttlSeconds);
    }

    /**
     * Removes or clears release.
     */
    public function release(): bool
    {
        return $this->cache->forgetIfValue($this->cacheKey(), $this->owner);
    }

    private function cacheKey(): string
    {
        return 'locks:' . $this->name;
    }
}
