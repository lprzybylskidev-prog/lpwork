<?php
declare(strict_types=1);

namespace LPwork\Http\Routing;

use FastRoute\Dispatcher;
use FastRoute\Dispatcher\GroupCountBased;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as RouteParser;
use FastRoute\DataGenerator\GroupCountBased as DataGenerator;
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

    /**
     * Builds dispatcher from pre-generated data.
     *
     * @param array<int|string, mixed> $data
     *
     * @return Dispatcher
     */
    public function createFromData(array $data): Dispatcher
    {
        return new GroupCountBased($data);
    }

    /**
     * Generates dispatcher data array without instantiating dispatcher.
     *
     * @param RouteCollection $routes
     *
     * @return array<int|string, mixed>
     */
    public function generateData(RouteCollection $routes): array
    {
        $collector = new RouteCollector(new RouteParser(), new DataGenerator());

        foreach ($routes->all() as $route) {
            $collector->addRoute($route->methods(), $route->path(), [
                "handler" => $route->handler(),
                "name" => $route->name(),
                "middleware" => $route->middleware(),
            ]);
        }

        return $collector->getData();
    }
}
