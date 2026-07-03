<?php

declare(strict_types=1);

namespace LPWork\Routing;

use LPWork\Http\Exceptions\MethodNotAllowedException;
use LPWork\Http\Exceptions\NotFoundException;
use LPWork\Routing\Exceptions\DuplicateRouteNameException;
use LPWork\Url\Exceptions\RouteNameNotFoundException;

/**
 * Represents the route collection framework component.
 */
final class RouteCollection
{
    /**
     * @var list<Route>
     */
    private array $routes = [];

    /**
     * @var array<string, Route>
     */
    private array $namedRoutes = [];

    /**
     * Adds an item to this component's registry or backing store.
     */
    public function add(Route $route): void
    {
        $this->routes[] = $route;

        $this->registerName($route);
    }

    /**
     * Registers or stores register name.
     */
    public function registerName(Route $route): void
    {
        $name = $route->name();

        if (!is_string($name)) {
            return;
        }

        if (isset($this->namedRoutes[$name]) && $this->namedRoutes[$name]->path() !== $route->path()) {
            throw new DuplicateRouteNameException($name);
        }

        $this->namedRoutes[$name] ??= $route;
    }

    public function match(string $method, string $path): RouteMatch
    {
        $allowedMethods = [];
        $fallbackMatch = null;

        foreach ($this->routes as $route) {
            if (!$route->matchesPath($path)) {
                continue;
            }

            foreach ($route->effectiveMethods() as $allowedMethod) {
                $allowedMethods[] = $allowedMethod;
            }

            if ($route->matchesExplicitMethod($method)) {
                return new RouteMatch($route, $route->parameters($path));
            }

            if ($fallbackMatch === null && $route->matchesMethod($method)) {
                $fallbackMatch = new RouteMatch($route, $route->parameters($path));
            }
        }

        if ($fallbackMatch instanceof RouteMatch) {
            return $fallbackMatch;
        }

        if ($allowedMethods !== []) {
            throw new MethodNotAllowedException(array_values(array_unique($allowedMethods)));
        }

        throw new NotFoundException();
    }

    /**
     * Returns named.
     */
    public function named(string $name): Route
    {
        return $this->namedRoutes[$name] ?? throw new RouteNameNotFoundException($name);
    }

    /**
     * @return list<Route>
     */
    public function all(): array
    {
        return $this->routes;
    }

    /**
     * Reports whether is empty.
     */
    public function isEmpty(): bool
    {
        return $this->routes === [];
    }
}
