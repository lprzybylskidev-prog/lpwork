<?php

declare(strict_types=1);

namespace LPWork\DebugBar\Providers;

use LPWork\DebugBar\Routes\DebugBarRoutes;
use LPWork\Routing\Contracts\RouteDefinition;
use LPWork\Routing\Providers\RoutesProvider;

/**
 * Registers debug bar routes provider services with the framework container.
 */
final class DebugBarRoutesProvider extends RoutesProvider
{
    /**
     * @return list<class-string<RouteDefinition>>
     */
    protected function routeDefinitions(): array
    {
        return [
            DebugBarRoutes::class,
        ];
    }
}
