<?php
declare(strict_types=1);

namespace LPwork\Provider\Common;

use DI\ContainerBuilder;
use LPwork\Http\Routing\RouteLoader;

/**
 * Registers routing loader.
 */
final class RoutingModuleProvider
{
    /**
     * @param ContainerBuilder $containerBuilder
     */
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            RouteLoader::class => \DI\autowire(RouteLoader::class)->constructor(
                \dirname(__DIR__, 2) . '/config/routes/routes.php',
                \dirname(__DIR__) . '/Http/Routes/routes.php',
            ),
        ]);
    }
}
