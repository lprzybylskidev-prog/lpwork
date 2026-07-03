<?php

declare(strict_types=1);

namespace LPWork\Routing;

use Closure;

/**
 * Represents the pending route framework component.
 */
final readonly class PendingRoute
{
    /**
     * @param Closure(string|list<string>): list<string> $expandMiddleware
     */
    public function __construct(
        private Route $route,
        private string $namePrefix,
        private RouteCollection $routes,
        private Closure $expandMiddleware,
    ) {}

    /**
     * Performs the route operation.
     */
    public function route(): Route
    {
        return $this->route;
    }

    /**
     * Returns the configured name for this object.
     */
    public function name(string $name): self
    {
        $this->route->setName($this->namePrefix . $name);
        $this->routes->registerName($this->route);

        return $this;
    }

    /**
     * Performs the route name operation.
     */
    public function routeName(): ?string
    {
        return $this->route->name();
    }

    /**
     * @param string|list<string> $middleware
     */
    public function middleware(string|array $middleware): self
    {
        $this->route->middleware(($this->expandMiddleware)($middleware));

        return $this;
    }

    /**
     * Performs the api operation.
     */
    public function api(): self
    {
        $this->route->api();

        return $this;
    }

    /**
     * Performs the where operation.
     */
    public function where(string $parameter, string $pattern): self
    {
        $this->route->where($parameter, $pattern);

        return $this;
    }
}
