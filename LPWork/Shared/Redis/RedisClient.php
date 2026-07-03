<?php

declare(strict_types=1);

namespace LPWork\Shared\Redis;

use LPWork\Shared\Exceptions\MissingPhpExtensionException;
use Redis;
use RedisException;
use RuntimeException;

/**
 * Represents the redis client framework component.
 */
final class RedisClient
{
    private ?Redis $redis = null;

    /**
     * Creates a new RedisClient instance.
     */
    public function __construct(
        private readonly RedisConfig $config,
        private readonly string $feature,
    ) {}

    /**
     * Returns the requested value from this component.
     */
    public function get(string $key): mixed
    {
        $value = $this->connection()->get($this->key($key));

        return $value === false ? null : $value;
    }

    /**
     * Sets a value in this component's backing store.
     */
    public function set(string $key, string $value, ?int $ttlSeconds = null): void
    {
        if ($ttlSeconds === null) {
            $this->connection()->set($this->key($key), $value);

            return;
        }

        $this->connection()->setex($this->key($key), $ttlSeconds, $value);
    }

    /**
     * Adds an item to this component's registry or backing store.
     */
    public function add(string $key, string $value, int $ttlSeconds): bool
    {
        $result = $this->connection()->set($this->key($key), $value, ['nx', 'ex' => $ttlSeconds]);

        return $result === true;
    }

    /**
     * Deletes the requested value from this component's backing store.
     */
    public function delete(string $key): int
    {
        return (int) $this->connection()->del($this->key($key));
    }

    /**
     * Removes or clears delete if value.
     */
    public function deleteIfValue(string $key, string $value): bool
    {
        $script = <<<'LUA'
            if redis.call("get", KEYS[1]) == ARGV[1] then
                return redis.call("del", KEYS[1])
            end

            return 0
            LUA;

        return $this->intValue($this->connection()->eval($script, [$this->key($key), $value], 1)) === 1;
    }

    /**
     * @return list<string>
     */
    public function keys(string $pattern): array
    {
        $keys = $this->connection()->keys($this->key($pattern));

        if (!is_array($keys)) {
            return [];
        }

        $prefixLength = strlen($this->config->prefix);
        $normalized = [];

        foreach ($keys as $key) {
            $normalized[] = $prefixLength > 0 ? substr($key, $prefixLength) : $key;
        }

        return $normalized;
    }

    /**
     * Performs the increment window operation.
     */
    public function incrementWindow(string $key, int $ttlSeconds): int
    {
        $redis = $this->connection();
        $key = $this->key($key);
        $attempts = $this->intValue($redis->incr($key));

        if ($attempts === 1) {
            $redis->expire($key, $ttlSeconds);
        }

        return $attempts;
    }

    /**
     * Performs the ttl operation.
     */
    public function ttl(string $key): int
    {
        return max(0, $this->intValue($this->connection()->ttl($this->key($key))));
    }

    /**
     * Performs the l push operation.
     */
    public function lPush(string $key, string $value): void
    {
        $this->connection()->lPush($this->key($key), $value);
    }

    /**
     * Performs the r pop operation.
     */
    public function rPop(string $key): ?string
    {
        $value = $this->connection()->rPop($this->key($key));

        return is_string($value) ? $value : null;
    }

    /**
     * @param array<string, string> $values
     */
    public function hMSet(string $key, array $values): void
    {
        $this->connection()->hMset($this->key($key), $values);
    }

    /**
     * @return array<string, string>
     */
    public function hGetAll(string $key): array
    {
        $values = $this->connection()->hGetAll($this->key($key));

        if (!is_array($values)) {
            return [];
        }

        $strings = [];

        foreach ($values as $field => $value) {
            if (is_string($field)) {
                $strings[$field] = $value;
            }
        }

        return $strings;
    }

    /**
     * Performs the z add operation.
     */
    public function zAdd(string $key, int $score, string $member): void
    {
        $this->connection()->zAdd($this->key($key), $score, $member);
    }

    /**
     * Performs the z rem operation.
     */
    public function zRem(string $key, string $member): void
    {
        $this->connection()->zRem($this->key($key), $member);
    }

    /**
     * Performs the z pop due operation.
     */
    public function zPopDue(string $key, int $now): ?string
    {
        $redis = $this->connection();
        $members = $redis->zRangeByScore($this->key($key), '-inf', (string) $now, ['limit' => [0, 1]]);

        if (!is_array($members) || !isset($members[0]) || !is_string($members[0])) {
            return null;
        }

        $removed = (int) $redis->zRem($this->key($key), $members[0]);

        return $removed === 1 ? $members[0] : null;
    }

    /**
     * Performs the publish operation.
     */
    public function publish(string $channel, string $payload): void
    {
        $this->connection()->publish($this->key($channel), $payload);
    }

    /**
     * Removes or clears clear pattern.
     */
    public function clearPattern(string $pattern): int
    {
        $keys = $this->keys($pattern);

        if ($keys === []) {
            return 0;
        }

        $deleted = 0;

        foreach ($keys as $key) {
            $deleted += $this->delete($key);
        }

        return $deleted;
    }

    private function key(string $key): string
    {
        return $this->config->prefix . $key;
    }

    private function intValue(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^[+-]?\d+$/', $value) === 1) {
            return (int) $value;
        }

        return 0;
    }

    private function connection(): Redis
    {
        if ($this->redis instanceof Redis) {
            return $this->redis;
        }

        if (!class_exists(Redis::class)) {
            throw new MissingPhpExtensionException('redis', $this->feature);
        }

        $redis = new Redis();

        try {
            $redis->connect($this->config->host, $this->config->port, $this->config->timeoutSeconds);

            if ($this->config->password !== null && $this->config->password !== '') {
                $redis->auth($this->config->password);
            }

            if ($this->config->database > 0) {
                $redis->select($this->config->database);
            }
        } catch (RedisException $exception) {
            throw new RuntimeException(sprintf('Could not connect to Redis for [%s].', $this->feature), previous: $exception);
        }

        return $this->redis = $redis;
    }
}
