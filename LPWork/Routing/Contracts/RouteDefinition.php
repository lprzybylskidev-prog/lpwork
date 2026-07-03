<?php

declare(strict_types=1);

namespace LPWork\Routing\Contracts;

use LPWork\Routing\Router;

/**
 * Defines the contract for route definition.
 */
interface RouteDefinition
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Router $router): void;
}
