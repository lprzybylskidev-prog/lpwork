<?php
declare(strict_types=1);

namespace LPwork\Cache;

use LPwork\Database\DatabaseConnectionManager;
use LPwork\Redis\RedisConnectionManager;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Psr16Cache;

/**
 * Resolves cache pools by name and exposes PSR-6/16 accessors.
 */
class CacheManager
{
    /**
     * @var CacheConfiguration
     */
    private CacheConfiguration $configuration;

    /**
     * @var CacheFactory
     */
    private CacheFactory $factory;

    /**
     * @var RedisConnectionManager
     */
    private RedisConnectionManager $redisConnections;

    /**
     * @var DatabaseConnectionManager
     */
    private DatabaseConnectionManager $databaseConnections;

    /**
     * @param CacheConfiguration         $configuration
     * @param CacheFactory               $factory
     * @param RedisConnectionManager     $redisConnections
     * @param DatabaseConnectionManager  $databaseConnections
     */
    public function __construct(
        CacheConfiguration $configuration,
        CacheFactory $factory,
        RedisConnectionManager $redisConnections,
        DatabaseConnectionManager $databaseConnections,
    ) {
        $this->configuration = $configuration;
        $this->factory = $factory;
        $this->redisConnections = $redisConnections;
        $this->databaseConnections = $databaseConnections;
    }

    /**
     * Returns default cache pool (PSR-6).
     *
     * @return CacheItemPoolInterface
     */
    public function defaultPool(): CacheItemPoolInterface
    {
        return $this->factory->createDefaultPool(
            $this->configuration,
            $this->redisConnections,
            $this->databaseConnections,
        );
    }

    /**
     * Returns named cache pool (PSR-6).
     *
     * @param string $name
     *
     * @return CacheItemPoolInterface
     */
    public function pool(string $name): CacheItemPoolInterface
    {
        return $this->factory->createPool(
            $name,
            $this->configuration,
            $this->redisConnections,
            $this->databaseConnections,
        );
    }

    /**
     * Returns default cache as PSR-16.
     *
     * @return Psr16Cache
     */
    public function defaultSimpleCache(): Psr16Cache
    {
        return new Psr16Cache($this->defaultPool());
    }

    /**
     * Returns named cache as PSR-16.
     *
     * @param string $name
     *
     * @return Psr16Cache
     */
    public function simpleCache(string $name): Psr16Cache
    {
        return new Psr16Cache($this->pool($name));
    }
}
