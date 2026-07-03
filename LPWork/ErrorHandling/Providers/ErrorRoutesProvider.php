<?php

declare(strict_types=1);

namespace LPWork\ErrorHandling\Providers;

use LPWork\ErrorHandling\Routes\ErrorRoutes;
use LPWork\Routing\Contracts\RouteDefinition;
use LPWork\Routing\Providers\RoutesProvider;

/**
 * Registers error routes provider services with the framework container.
 */
final class ErrorRoutesProvider extends RoutesProvider
{
    /**
     * @return list<class-string<RouteDefinition>>
     */
    protected function routeDefinitions(): array
    {
        return [
            ErrorRoutes::class,
        ];
    }
}
