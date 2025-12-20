<?php
declare(strict_types=1);

namespace LPwork\Http\Routing;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

/**
 * Builds a FastRoute dispatcher from route definitions.
 */
class FastRouteDispatcherFactory
{
    /**
     * @param RouteCollection $routes
     *
     * @return Dispatcher
     */
    public function create(RouteCollection $routes): Dispatcher
    {
        return simpleDispatcher(static function (
            RouteCollector $collector,
        ) use ($routes): void {
            foreach ($routes->all() as $route) {
                $collector->addRoute($route->methods(), $route->path(), [
                    "handler" => $route->handler(),
                    "name" => $route->name(),
                    "middleware" => $route->middleware(),
                ]);
            }
        });
    }
}
