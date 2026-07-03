<?php

declare(strict_types=1);

namespace Tests\support\routing;

use LPWork\Routing\Contracts\RouteDefinition;
use LPWork\Routing\Router;

final class ProviderRouteDefinition implements RouteDefinition
{
    public function register(Router $router): void
    {
        $router->get('/provider-route', [TestController::class, 'index'])->name('provider.route');
    }
}
