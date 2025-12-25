<?php
declare(strict_types=1);

namespace LPwork\Provider\Common;

use DI\ContainerBuilder;
use LPwork\Config\Contract\ConfigRepositoryInterface;
use LPwork\Database\Contract\DatabaseConnectionInterface;
use LPwork\Database\Contract\DatabaseConnectionManagerInterface;
use LPwork\Database\DatabaseConnectionManager;
use LPwork\Database\DatabaseTimezoneConfigurator;

/**
 * Registers database connections.
 */
final class DatabaseModuleProvider
{
    /**
     * @param ContainerBuilder $containerBuilder
     */
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            DatabaseTimezoneConfigurator::class => \DI\autowire(
                DatabaseTimezoneConfigurator::class,
            ),
            DatabaseConnectionManager::class => \DI\factory(static function (
                ConfigRepositoryInterface $config,
                DatabaseTimezoneConfigurator $timezoneConfigurator,
            ): DatabaseConnectionManager {
                $connections = $config->get('database.connections', []);
                $default = $config->getString('database.default_connection', 'default');

                return new DatabaseConnectionManager($connections, $default, $timezoneConfigurator);
            }),
            DatabaseConnectionManagerInterface::class => \DI\get(DatabaseConnectionManager::class),
            DatabaseConnectionInterface::class => \DI\factory(static function (
                DatabaseConnectionManagerInterface $manager,
                ConfigRepositoryInterface $config,
            ): DatabaseConnectionInterface {
                $default = $config->getString('database.default_connection', 'default');

                return $manager->get($default);
            }),
        ]);
    }
}
