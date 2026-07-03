<?php

declare(strict_types=1);

namespace LPWork\Routing;

/**
 * Represents the route match framework component.
 */
final readonly class RouteMatch
{
    /**
     * @param array<string, string> $parameters
     */
    public function __construct(
        private Route $route,
        private array $parameters = [],
    ) {}

    /**
     * Performs the route operation.
     */
    public function route(): Route
    {
        return $this->route;
    }

    /**
     * @return array<string, string>
     */
    public function parameters(): array
    {
        return $this->parameters;
    }

    /**
     * Performs the parameter operation.
     */
    public function parameter(string $name, ?string $default = null): ?string
    {
        return $this->parameters[$name] ?? $default;
    }
}
