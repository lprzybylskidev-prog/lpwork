<?php

declare(strict_types=1);

namespace LPWork\Health\Providers;

use LPWork\Container\Container;
use LPWork\Health\Checks\ConsoleHealthCheck;
use LPWork\Health\Checks\RoutingHealthCheck;
use LPWork\Health\Routes\HealthRoutes;
use LPWork\Routing\Contracts\RouteDefinition;
use LPWork\Routing\Providers\RoutesProvider;

/**
 * Registers health routes provider services with the framework container.
 */
final class HealthRoutesProvider extends RoutesProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        parent::register($container);

        $container->singleton(RoutingHealthCheck::class);
        $container->singleton(ConsoleHealthCheck::class, static fn(Container $container): ConsoleHealthCheck => new ConsoleHealthCheck($container));
        $this->registerHealthCheck($container, ConsoleHealthCheck::class);
        $this->registerHealthCheck($container, RoutingHealthCheck::class);
    }

    /**
     * @return list<class-string<RouteDefinition>>
     */
    protected function routeDefinitions(): array
    {
        return [
            HealthRoutes::class,
        ];
    }
}
