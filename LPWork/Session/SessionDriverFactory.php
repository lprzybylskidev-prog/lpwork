<?php

declare(strict_types=1);

namespace LPWork\Session;

use LPWork\Cache\CacheManager;
use LPWork\Config\ArrayConfigReader;
use LPWork\Database\Contracts\Connection;
use LPWork\Database\DatabaseManager;
use LPWork\Security\SecurityConfig;
use LPWork\Session\Contracts\SessionDriver;
use LPWork\Session\Drivers\CacheSessionDriver;
use LPWork\Session\Drivers\DatabaseSessionDriver;
use LPWork\Session\Drivers\InMemorySessionDriver;
use LPWork\Session\Drivers\PhpSessionDriver;
use LPWork\Session\Drivers\RedisSessionDriver;
use LPWork\Session\Exceptions\InvalidSessionConfigException;
use LPWork\Session\Exceptions\InvalidSessionDriverException;
use LPWork\Session\Exceptions\MissingSessionConfigException;
use LPWork\Shared\Redis\RedisClient;
use LPWork\Shared\Redis\RedisConfigFactory;
use LPWork\Time\Contracts\Clock;
use LPWork\Time\SystemClock;

/**
 * Creates session driver factory instances from framework configuration.
 */
final readonly class SessionDriverFactory
{
    /**
     * Creates a new SessionDriverFactory instance.
     */
    public function __construct(
        private ?SecurityConfig $security = null,
        private ?CacheManager $cache = null,
        private ?DatabaseManager $database = null,
        private Clock $clock = new SystemClock(),
        private RedisConfigFactory $redis = new RedisConfigFactory(),
    ) {}

    /**
     * @param array<array-key, mixed> $config
     */
    public function create(array $config, string $key): SessionDriver
    {
        $reader = $this->reader($config);
        $type = $reader->string('driver', "{$key}.driver");

        return match ($type) {
            'php' => new PhpSessionDriver(
                name: $reader->string('name', "{$key}.name"),
                lifetime: $reader->int('lifetime', "{$key}.lifetime"),
                path: $reader->string('path', "{$key}.path"),
                domain: $reader->optionalString('domain', "{$key}.domain", allowEmpty: true) ?? '',
                secure: $this->secureCookie($reader->bool('secure', "{$key}.secure")),
                httpOnly: $reader->bool('http_only', "{$key}.http_only"),
                sameSite: $reader->string('same_site', "{$key}.same_site"),
                useStrictMode: $reader->bool('use_strict_mode', "{$key}.use_strict_mode"),
            ),
            'memory' => new InMemorySessionDriver(),
            'cache' => new CacheSessionDriver(
                cache: $this->cache($reader->string('store', "{$key}.store")),
                name: $reader->string('name', "{$key}.name"),
                lifetime: $reader->int('lifetime', "{$key}.lifetime"),
                path: $reader->string('path', "{$key}.path"),
                domain: $reader->optionalString('domain', "{$key}.domain", allowEmpty: true) ?? '',
                secure: $this->secureCookie($reader->bool('secure', "{$key}.secure")),
                httpOnly: $reader->bool('http_only', "{$key}.http_only"),
                sameSite: $reader->string('same_site', "{$key}.same_site"),
            ),
            'redis' => new RedisSessionDriver(
                redis: new RedisClient($this->redis->create($reader, $config, $key), "session driver [{$key}]"),
                name: $reader->string('name', "{$key}.name"),
                lifetime: $reader->int('lifetime', "{$key}.lifetime"),
                path: $reader->string('path', "{$key}.path"),
                domain: $reader->optionalString('domain', "{$key}.domain", allowEmpty: true) ?? '',
                secure: $this->secureCookie($reader->bool('secure', "{$key}.secure")),
                httpOnly: $reader->bool('http_only', "{$key}.http_only"),
                sameSite: $reader->string('same_site', "{$key}.same_site"),
            ),
            'database' => new DatabaseSessionDriver(
                connection: $this->database($reader->optionalString('connection', "{$key}.connection", allowEmpty: true)),
                table: $reader->string('table', "{$key}.table"),
                clock: $this->clock,
                name: $reader->string('name', "{$key}.name"),
                lifetime: $reader->int('lifetime', "{$key}.lifetime"),
                path: $reader->string('path', "{$key}.path"),
                domain: $reader->optionalString('domain', "{$key}.domain", allowEmpty: true) ?? '',
                secure: $this->secureCookie($reader->bool('secure', "{$key}.secure")),
                httpOnly: $reader->bool('http_only', "{$key}.http_only"),
                sameSite: $reader->string('same_site', "{$key}.same_site"),
            ),
            default => throw new InvalidSessionDriverException($type),
        };
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private function reader(array $config): ArrayConfigReader
    {
        return new ArrayConfigReader(
            config: $config,
            missingException: static fn(string $key): MissingSessionConfigException => new MissingSessionConfigException($key),
            invalidException: static fn(string $key): InvalidSessionConfigException => new InvalidSessionConfigException($key),
        );
    }

    private function secureCookie(bool $configured): bool
    {
        return $configured || ($this->security?->usesSecureCookies() ?? false);
    }

    private function cache(string $store): \LPWork\Cache\CacheStore
    {
        if ($this->cache === null) {
            throw new MissingSessionConfigException('cache');
        }

        return $this->cache->store($store);
    }

    private function database(?string $connection): Connection
    {
        if ($this->database === null) {
            throw new MissingSessionConfigException('database');
        }

        return $connection === null || $connection === '' ? $this->database->default() : $this->database->connection($connection);
    }
}
