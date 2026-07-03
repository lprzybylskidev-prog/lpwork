<?php

declare(strict_types=1);

namespace LPWork\Session\Drivers;

use LPWork\Shared\Redis\RedisClient;

/**
 * Represents the redis session driver framework component.
 */
final class RedisSessionDriver extends PersistentSessionDriver
{
    /**
     * Creates a new RedisSessionDriver instance.
     */
    public function __construct(
        private readonly RedisClient $redis,
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
        $value = $this->redis->get($this->key($id));

        if (!is_string($value)) {
            return null;
        }

        $data = unserialize($value);

        return $this->sessionData($data);
    }

    protected function write(string $id, array $data, int $ttlSeconds): void
    {
        $this->redis->set($this->key($id), serialize($data), $ttlSeconds);
    }

    protected function delete(string $id): void
    {
        $this->redis->delete($this->key($id));
    }

    private function key(string $id): string
    {
        return 'sessions:' . $id;
    }
}
