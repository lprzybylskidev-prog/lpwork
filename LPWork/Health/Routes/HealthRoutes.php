<?php

declare(strict_types=1);

namespace LPWork\Health\Routes;

use LPWork\Health\Controllers\HealthCheckController;
use LPWork\Routing\Contracts\RouteDefinition;
use LPWork\Routing\Router;

/**
 * Represents the health routes framework component.
 */
final readonly class HealthRoutes implements RouteDefinition
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Router $router): void
    {
        $router->get('/health', [HealthCheckController::class, 'show'])->name('health.show');
    }
}
