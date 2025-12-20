<?php
declare(strict_types=1);

namespace LPwork\Redis;

use LPwork\Redis\Contract\RedisConnectionInterface;
use LPwork\Redis\Exception\RedisConnectionNotFoundException;

/**
 * Manages named Redis connections.
 */
class RedisConnectionManager
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $configurations;

    /**
     * @var array<string, RedisConnectionInterface>
     */
    private array $connections = [];

    /**
     * @var string
     */
    private string $defaultConnection;

    /**
     * @param array<string, array<string, mixed>> $configurations
     * @param string                              $defaultConnection
     */
    public function __construct(
        array $configurations,
        string $defaultConnection = "default",
    ) {
        $this->configurations = $configurations;
        $this->defaultConnection = $defaultConnection;
    }

    /**
     * Returns a connection by name.
     *
     * @param string|null $name
     *
     * @return RedisConnectionInterface
     */
    public function get(?string $name = null): RedisConnectionInterface
    {
        $connectionName = $name ?? $this->defaultConnection;

        if (isset($this->connections[$connectionName])) {
            return $this->connections[$connectionName];
        }

        if (!isset($this->configurations[$connectionName])) {
            throw new RedisConnectionNotFoundException(
                \sprintf(
                    'Redis connection "%s" is not configured.',
                    $connectionName,
                ),
            );
        }

        $config = new RedisConfig($this->configurations[$connectionName]);
        $connection = new PredisConnection($config);

        $this->connections[$connectionName] = $connection;

        return $connection;
    }
}
