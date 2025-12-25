<?php
declare(strict_types=1);

namespace LPwork\Provider\Common;

use DI\ContainerBuilder;
use LPwork\Config\Contract\ConfigRepositoryInterface;
use LPwork\Console\Command\WebSocketServeCommand;
use LPwork\WebSocket\Contract\WebSocketComponentRegistryInterface;
use LPwork\WebSocket\WebSocketConfiguration;
use LPwork\WebSocket\WebSocketServerFactory;

/**
 * Registers WebSocket components and command.
 */
final class WebSocketModuleProvider
{
    /**
     * @param ContainerBuilder $containerBuilder
     */
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            WebSocketConfiguration::class => \DI\factory(static function (
                ConfigRepositoryInterface $config,
            ): WebSocketConfiguration {
                $wsConfig = (array) $config->get('websocket', []);

                return new WebSocketConfiguration($wsConfig);
            }),
            WebSocketComponentRegistryInterface::class => \DI\get(\Config\WebSocketProvider::class),
            WebSocketServerFactory::class => \DI\autowire(WebSocketServerFactory::class),
            WebSocketServeCommand::class => \DI\autowire(WebSocketServeCommand::class),
        ]);
    }
}
