<?php

declare(strict_types=1);

namespace LPWork\Broadcasting;

use LPWork\Broadcasting\Contracts\Broadcaster;
use LPWork\Broadcasting\Drivers\InMemoryBroadcaster;
use LPWork\Broadcasting\Drivers\LogBroadcaster;
use LPWork\Broadcasting\Drivers\NullBroadcaster;
use LPWork\Broadcasting\Drivers\PusherBroadcaster;
use LPWork\Broadcasting\Drivers\RedisBroadcaster;
use LPWork\Broadcasting\Exceptions\InvalidBroadcastingConfigException;
use LPWork\Broadcasting\Exceptions\InvalidBroadcastingDriverException;
use LPWork\Config\ArrayConfigReader;
use LPWork\Logging\Contracts\Logger;
use LPWork\Shared\Redis\RedisClient;
use LPWork\Shared\Redis\RedisConfigFactory;

/**
 * Creates broadcast driver factory instances from framework configuration.
 */
final readonly class BroadcastDriverFactory
{
    /**
     * Creates a new BroadcastDriverFactory instance.
     */
    public function __construct(
        private ?Logger $logger = null,
        private RedisConfigFactory $redis = new RedisConfigFactory(),
    ) {}

    /**
     * @param array<array-key, mixed> $config
     */
    public function create(string $name, array $config, string $key): Broadcaster
    {
        $reader = new ArrayConfigReader(
            config: $config,
            missingException: static fn(string $name): InvalidBroadcastingConfigException => new InvalidBroadcastingConfigException($name),
            invalidException: static fn(string $name): InvalidBroadcastingConfigException => new InvalidBroadcastingConfigException($name),
        );
        $driver = $reader->string('driver', "{$key}.driver");

        return match ($driver) {
            'none' => new NullBroadcaster($name),
            'log' => new LogBroadcaster($name, $this->logger),
            'sync' => new InMemoryBroadcaster($name),
            'redis' => new RedisBroadcaster($name, new RedisClient($this->redis->create($reader, $config, $key), "broadcast connection [{$key}]")),
            'pusher' => new PusherBroadcaster(
                name: $name,
                appId: $reader->string('app_id', "{$key}.app_id"),
                key: $reader->string('key', "{$key}.key"),
                secret: $reader->string('secret', "{$key}.secret"),
                endpoint: $reader->string('endpoint', "{$key}.endpoint"),
            ),
            default => throw new InvalidBroadcastingDriverException($driver),
        };
    }
}
