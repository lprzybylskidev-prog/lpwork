<?php

declare(strict_types=1);

namespace LPWork\DebugBar\Routes;

use LPWork\DebugBar\DebugBarController;
use LPWork\Routing\Contracts\RouteDefinition;
use LPWork\Routing\Router;

/**
 * Represents the debug bar routes framework component.
 */
final readonly class DebugBarRoutes implements RouteDefinition
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Router $router): void
    {
        $router->get('/__lpwork/debugbar/requests', [DebugBarController::class, 'requests'])->name('debugbar.requests');
        $router->get('/__lpwork/debugbar/request', [DebugBarController::class, 'request'])->name('debugbar.request');
    }
}
