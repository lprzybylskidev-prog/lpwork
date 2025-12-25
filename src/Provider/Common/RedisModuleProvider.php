<?php
declare(strict_types=1);

namespace LPwork\Provider\Common;

use DI\ContainerBuilder;
use LPwork\Config\Contract\ConfigRepositoryInterface;
use LPwork\Redis\Contract\RedisConnectionInterface;
use LPwork\Redis\RedisConnectionManager;

/**
 * Registers Redis connections.
 */
final class RedisModuleProvider
{
    /**
     * @param ContainerBuilder $containerBuilder
     */
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            RedisConnectionManager::class => \DI\factory(static function (
                ConfigRepositoryInterface $config,
            ): RedisConnectionManager {
                $connections = $config->get('redis.connections', []);
                $default = $config->getString('redis.default_connection', 'default');

                return new RedisConnectionManager($connections, $default);
            }),
            RedisConnectionInterface::class => \DI\factory(static function (
                RedisConnectionManager $manager,
                ConfigRepositoryInterface $config,
            ): RedisConnectionInterface {
                $default = $config->getString('redis.default_connection', 'default');

                return $manager->get($default);
            }),
        ]);
    }
}
