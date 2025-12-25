<?php
declare(strict_types=1);

namespace LPwork\Logging\Contract;

use LPwork\Logging\LogConfiguration;
use LPwork\Redis\Contract\RedisConnectionManagerInterface;
use LPwork\Database\Contract\DatabaseConnectionManagerInterface;

/**
 * Contract for building PSR-3 loggers.
 */
interface LogFactoryInterface
{
    /**
     * @param LogConfiguration                   $configuration
     * @param RedisConnectionManagerInterface    $redisConnections
     * @param DatabaseConnectionManagerInterface $databaseConnections
     * @param \Carbon\CarbonImmutable            $clock
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function createDefault(
        LogConfiguration $configuration,
        RedisConnectionManagerInterface $redisConnections,
        DatabaseConnectionManagerInterface $databaseConnections,
        \Carbon\CarbonImmutable $clock,
    ): \Psr\Log\LoggerInterface;

    /**
     * @param string                             $name
     * @param LogConfiguration                   $configuration
     * @param RedisConnectionManagerInterface    $redisConnections
     * @param DatabaseConnectionManagerInterface $databaseConnections
     * @param \Carbon\CarbonImmutable            $clock
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function createChannel(
        string $name,
        LogConfiguration $configuration,
        RedisConnectionManagerInterface $redisConnections,
        DatabaseConnectionManagerInterface $databaseConnections,
        \Carbon\CarbonImmutable $clock,
    ): \Psr\Log\LoggerInterface;
}
