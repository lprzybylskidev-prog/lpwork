<?php
declare(strict_types=1);

namespace LPwork\Provider\Common;

use DI\ContainerBuilder;
use LPwork\Cache\CacheConfiguration;
use LPwork\Cache\Contract\CacheFactoryInterface;
use LPwork\Cache\Contract\CacheManagerInterface;
use LPwork\Cache\Contract\CacheProviderInterface;
use LPwork\Provider\Common\CacheProvider;
use LPwork\Database\Contract\DatabaseConnectionManagerInterface;
use LPwork\Redis\Contract\RedisConnectionManagerInterface;
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
            CacheFactoryInterface::class => \DI\autowire(\LPwork\Cache\CacheFactory::class),
            CacheItemPoolInterface::class => \DI\factory(static function (
                CacheFactoryInterface $factory,
                CacheConfiguration $configuration,
                RedisConnectionManagerInterface $redisConnections,
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
            CacheProviderInterface::class => \DI\autowire(CacheProvider::class),
            CacheManagerInterface::class => \DI\autowire(\LPwork\Cache\CacheManager::class),
        ]);
    }
}
