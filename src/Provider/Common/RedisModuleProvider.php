<?php
declare(strict_types=1);

namespace LPwork\Provider\Common;

use DI\ContainerBuilder;
use LPwork\Config\Contract\ConfigRepositoryInterface;
use LPwork\Redis\Contract\RedisConnectionInterface;
use LPwork\Redis\Contract\RedisConnectionManagerInterface;
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
            RedisConnectionManagerInterface::class => \DI\get(RedisConnectionManager::class),
            RedisConnectionInterface::class => \DI\factory(static function (
                RedisConnectionManagerInterface $manager,
                ConfigRepositoryInterface $config,
            ): RedisConnectionInterface {
                $default = $config->getString('redis.default_connection', 'default');

                return $manager->get($default);
            }),
        ]);
    }
}
