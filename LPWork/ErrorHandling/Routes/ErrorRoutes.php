<?php

declare(strict_types=1);

namespace LPWork\ErrorHandling\Routes;

use LPWork\ErrorHandling\Controllers\ErrorPageController;
use LPWork\Routing\Contracts\RouteDefinition;
use LPWork\Routing\Router;

/**
 * Represents the error routes framework component.
 */
final readonly class ErrorRoutes implements RouteDefinition
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Router $router): void
    {
        $router->get('/error/{code}', [ErrorPageController::class, 'show'])->name('error.show');
    }
}
