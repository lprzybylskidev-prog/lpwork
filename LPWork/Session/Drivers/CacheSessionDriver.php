<?php

declare(strict_types=1);

namespace LPWork\Session\Drivers;

use LPWork\Cache\CacheStore;

/**
 * Represents the cache session driver framework component.
 */
final class CacheSessionDriver extends PersistentSessionDriver
{
    /**
     * Creates a new CacheSessionDriver instance.
     */
    public function __construct(
        private readonly CacheStore $cache,
        string $name,
        int $lifetime,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = true,
        string $sameSite = 'Lax',
    ) {
        parent::__construct($name, $lifetime, $path, $domain, $secure, $httpOnly, $sameSite);
    }

    protected function read(string $id): ?array
    {
        $data = $this->cache->get($this->key($id));

        return $this->sessionData($data);
    }

    protected function write(string $id, array $data, int $ttlSeconds): void
    {
        $this->cache->put($this->key($id), $data, $ttlSeconds);
    }

    protected function delete(string $id): void
    {
        $this->cache->forget($this->key($id));
    }

    private function key(string $id): string
    {
        return 'sessions:' . $id;
    }
}
