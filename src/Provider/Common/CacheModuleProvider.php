<?php
declare(strict_types=1);

namespace LPwork\Provider\Common;

use DI\ContainerBuilder;
use LPwork\Cache\CacheConfiguration;
use LPwork\Cache\CacheFactory;
use LPwork\Cache\CacheManager;
use LPwork\Cache\Contract\CacheManagerInterface;
use LPwork\Cache\Contract\CacheProviderInterface;
use LPwork\Cache\DefaultCacheProvider;
use LPwork\Database\Contract\DatabaseConnectionManagerInterface;
use LPwork\Redis\RedisConnectionManager;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Psr16Cache;

/**
 * Registers cache pools and providers.
 */
final class CacheModuleProvider
{
    /**
     * @param ContainerBuilder $containerBuilder
     */
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            CacheFactory::class => \DI\autowire(CacheFactory::class),
            CacheItemPoolInterface::class => \DI\factory(static function (
                CacheFactory $factory,
                CacheConfiguration $configuration,
                RedisConnectionManager $redisConnections,
                DatabaseConnectionManagerInterface $databaseConnections,
            ): CacheItemPoolInterface {
                return $factory->createDefaultPool(
                    $configuration,
                    $redisConnections,
                    $databaseConnections,
                );
            }),
            Psr16Cache::class => \DI\factory(static function (
                CacheItemPoolInterface $pool,
            ): Psr16Cache {
                return new Psr16Cache($pool);
            }),
            CacheProviderInterface::class => \DI\autowire(DefaultCacheProvider::class),
            CacheManager::class => \DI\autowire(CacheManager::class),
            CacheManagerInterface::class => \DI\get(CacheManager::class),
        ]);
    }
}
