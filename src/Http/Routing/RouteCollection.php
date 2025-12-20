<?php
declare(strict_types=1);

namespace LPwork\Http\Routing;

/**
 * Collects route definitions with support for grouping.
 */
class RouteCollection
{
    /**
     * @var array<string, Route>
     */
    private array $routes = [];

    /**
     * @var array<int, string>
     */
    private array $groupPrefixStack = [];

    /**
     * @var array<int, string>
     */
    private array $groupNameStack = [];

    /**
     * @var array<int, array<int, string>>
     */
    private array $groupMiddlewareStack = [];

    /**
     * @param string        $path
     * @param callable      $handler
     * @param string|null   $name
     * @param array<int, string> $middleware
     *
     * @return Route
     */
    public function get(
        string $path,
        callable $handler,
        ?string $name = null,
        array $middleware = [],
    ): Route {
        return $this->add(["GET"], $path, $handler, $name, $middleware);
    }

    /**
     * @param string        $path
     * @param callable      $handler
     * @param string|null   $name
     * @param array<int, string> $middleware
     *
     * @return Route
     */
    public function post(
        string $path,
        callable $handler,
        ?string $name = null,
        array $middleware = [],
    ): Route {
        return $this->add(["POST"], $path, $handler, $name, $middleware);
    }

    /**
     * @param string        $path
     * @param callable      $handler
     * @param string|null   $name
     * @param array<int, string> $middleware
     *
     * @return Route
     */
    public function put(
        string $path,
        callable $handler,
        ?string $name = null,
        array $middleware = [],
    ): Route {
        return $this->add(["PUT"], $path, $handler, $name, $middleware);
    }

    /**
     * @param string        $path
     * @param callable      $handler
     * @param string|null   $name
     * @param array<int, string> $middleware
     *
     * @return Route
     */
    public function patch(
        string $path,
        callable $handler,
        ?string $name = null,
        array $middleware = [],
    ): Route {
        return $this->add(["PATCH"], $path, $handler, $name, $middleware);
    }

    /**
     * @param string        $path
     * @param callable      $handler
     * @param string|null   $name
     * @param array<int, string> $middleware
     *
     * @return Route
     */
    public function delete(
        string $path,
        callable $handler,
        ?string $name = null,
        array $middleware = [],
    ): Route {
        return $this->add(["DELETE"], $path, $handler, $name, $middleware);
    }

    /**
     * @param array<int, string> $methods
     * @param string             $path
     * @param callable           $handler
     * @param string|null        $name
     * @param array<int, string> $middleware
     *
     * @return Route
     */
    public function match(
        array $methods,
        string $path,
        callable $handler,
        ?string $name = null,
        array $middleware = [],
    ): Route {
        return $this->add($methods, $path, $handler, $name, $middleware);
    }

    /**
     * @param string        $path
     * @param callable      $handler
     * @param string|null   $name
     * @param array<int, string> $middleware
     *
     * @return Route
     */
    public function any(
        string $path,
        callable $handler,
        ?string $name = null,
        array $middleware = [],
    ): Route {
        return $this->add(
            ["GET", "POST", "PUT", "PATCH", "DELETE", "OPTIONS"],
            $path,
            $handler,
            $name,
            $middleware,
        );
    }

    /**
     * @param array<string, mixed> $attributes
     * @param callable             $callback
     *
     * @return void
     */
    public function group(array $attributes, callable $callback): void
    {
        $this->groupPrefixStack[] = $attributes["prefix"] ?? "";
        $this->groupNameStack[] = $attributes["name"] ?? "";
        $this->groupMiddlewareStack[] = $attributes["middleware"] ?? [];

        $callback($this);

        \array_pop($this->groupPrefixStack);
        \array_pop($this->groupNameStack);
        \array_pop($this->groupMiddlewareStack);
    }

    /**
     * @return array<int, Route>
     */
    public function all(): array
    {
        return \array_values($this->routes);
    }

    /**
     * @param array<int, string> $methods
     * @param string             $path
     * @param callable           $handler
     * @param string|null        $name
     * @param array<int, string> $middleware
     *
     * @return Route
     */
    private function add(
        array $methods,
        string $path,
        callable $handler,
        ?string $name = null,
        array $middleware = [],
    ): Route {
        $fullPath = $this->applyGroupPrefix($path);
        $fullName = $this->applyGroupName($name);
        $combinedMiddleware = $this->mergeMiddleware($middleware);

        $route = new Route(
            $methods,
            $fullPath,
            $handler,
            $fullName,
            $combinedMiddleware,
        );
        $this->routes[$this->routeKey($methods, $fullPath)] = $route;

        return $route;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private function applyGroupPrefix(string $path): string
    {
        $prefix = "";
        foreach ($this->groupPrefixStack as $groupPrefix) {
            $prefix .= $groupPrefix;
        }

        return $prefix . $path;
    }

    /**
     * @param string|null $name
     *
     * @return string|null
     */
    private function applyGroupName(?string $name): ?string
    {
        $prefix = "";
        foreach ($this->groupNameStack as $groupName) {
            $prefix .= $groupName;
        }

        if ($name === null) {
            return $prefix !== "" ? $prefix : null;
        }

        return $prefix . $name;
    }

    /**
     * @param array<int, string> $routeMiddleware
     *
     * @return array<int, string>
     */
    private function mergeMiddleware(array $routeMiddleware): array
    {
        $merged = [];

        foreach ($this->groupMiddlewareStack as $groupMiddleware) {
            $merged = \array_merge($merged, $groupMiddleware);
        }

        return \array_merge($merged, $routeMiddleware);
    }

    /**
     * @param array<int, string> $methods
     * @param string             $path
     *
     * @return string
     */
    private function routeKey(array $methods, string $path): string
    {
        $normalizedMethods = $methods;
        \sort($normalizedMethods);

        return \implode(",", $normalizedMethods) . "|" . $path;
    }
}
