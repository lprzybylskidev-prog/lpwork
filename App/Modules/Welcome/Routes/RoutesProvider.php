<?php

declare(strict_types=1);

namespace App\Modules\Welcome\Routes;

use LPWork\Routing\Contracts\RouteDefinition;
use LPWork\Routing\Providers\RoutesProvider as BaseRoutesProvider;

final class RoutesProvider extends BaseRoutesProvider
{
    /**
     * @return list<class-string<RouteDefinition>>
     */
    protected function routeDefinitions(): array
    {
        return [
            WebRoutes::class,
        ];
    }
}
