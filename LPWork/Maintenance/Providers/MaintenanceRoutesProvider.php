<?php

declare(strict_types=1);

namespace LPWork\Maintenance\Providers;

use LPWork\Maintenance\Routes\MaintenanceRoutes;
use LPWork\Routing\Contracts\RouteDefinition;
use LPWork\Routing\Providers\RoutesProvider;

/**
 * Registers maintenance routes provider services with the framework container.
 */
final class MaintenanceRoutesProvider extends RoutesProvider
{
    /**
     * @return list<class-string<RouteDefinition>>
     */
    protected function routeDefinitions(): array
    {
        return [
            MaintenanceRoutes::class,
        ];
    }
}
