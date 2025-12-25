<?php
declare(strict_types=1);

namespace LPwork\Cache\Contract;

use LPwork\Cache\CacheConfiguration;
use LPwork\Database\Contract\DatabaseConnectionManagerInterface;
use LPwork\Redis\Contract\RedisConnectionManagerInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Contract for building cache pools.
 */
interface CacheFactoryInterface
{
    /**
     * @param CacheConfiguration                   $configuration
     * @param RedisConnectionManagerInterface      $redisConnections
     * @param DatabaseConnectionManagerInterface   $databaseConnections
     *
     * @return CacheItemPoolInterface
     */
    public function createDefaultPool(
        CacheConfiguration $configuration,
        RedisConnectionManagerInterface $redisConnections,
        DatabaseConnectionManagerInterface $databaseConnections,
    ): CacheItemPoolInterface;

    /**
     * @param string                               $name
     * @param CacheConfiguration                   $configuration
     * @param RedisConnectionManagerInterface      $redisConnections
     * @param DatabaseConnectionManagerInterface   $databaseConnections
     *
     * @return CacheItemPoolInterface
     */
    public function createPool(
        string $name,
        CacheConfiguration $configuration,
        RedisConnectionManagerInterface $redisConnections,
        DatabaseConnectionManagerInterface $databaseConnections,
    ): CacheItemPoolInterface;
}
