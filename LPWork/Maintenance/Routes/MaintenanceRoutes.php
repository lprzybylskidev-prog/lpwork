<?php

declare(strict_types=1);

namespace LPWork\Maintenance\Routes;

use LPWork\Maintenance\Controllers\MaintenancePageController;
use LPWork\Routing\Contracts\RouteDefinition;
use LPWork\Routing\Router;

/**
 * Represents the maintenance routes framework component.
 */
final readonly class MaintenanceRoutes implements RouteDefinition
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Router $router): void
    {
        $router->get('/maintenance', [MaintenancePageController::class, 'show'])->name('maintenance.show');
    }
}
