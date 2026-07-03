<?php

declare(strict_types=1);

namespace LPWork\Routing;

use Closure;
use LPWork\Filesystem\Filesystem;
use LPWork\Middleware\Contracts\Middleware;
use LPWork\Routing\Exceptions\InvalidResourceRouteException;
use LPWork\Routing\Exceptions\InvalidRoutingConfigException;
use LPWork\Routing\Exceptions\RouteFileNotFoundException;

/**
 * Registers HTTP routes, route groups, middleware declarations, and resource route conventions.
 */
final class Router
{
    /**
     * @var list<array{method: string, path: string, action: string, name: string}>
     */
    private const RESOURCE_ROUTES = [
        ['method' => 'GET', 'path' => '/%s', 'action' => 'index', 'name' => 'index'],
        ['method' => 'GET', 'path' => '/%s/create', 'action' => 'create', 'name' => 'create'],
        ['method' => 'POST', 'path' => '/%s', 'action' => 'store', 'name' => 'store'],
        ['method' => 'GET', 'path' => '/%s/{%s}', 'action' => 'show', 'name' => 'show'],
        ['method' => 'GET', 'path' => '/%s/{%s}/edit', 'action' => 'edit', 'name' => 'edit'],
        ['method' => 'PUT', 'path' => '/%s/{%s}', 'action' => 'update', 'name' => 'update'],
        ['method' => 'PATCH', 'path' => '/%s/{%s}', 'action' => 'update', 'name' => 'update'],
        ['method' => 'DELETE', 'path' => '/%s/{%s}', 'action' => 'destroy', 'name' => 'destroy'],
    ];

    /**
     * @var list<array{prefix: string, name: string, middleware: list<string>, api: bool}>
     */
    private array $groups = [];

    /**
     * @var array<string, string>
     */
    private array $middlewareAliases = [];

    /**
     * @var array<string, list<string>>
     */
    private array $middlewareGroups = [];

    /**
     * @var list<string>
     */
    private array $globalMiddleware = [];

    /**
     * Creates a router with an optional existing route collection for cache loading or tests.
     */
    public function __construct(
        private readonly RouteCollection $routes = new RouteCollection(),
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {}

    /**
     * Returns the mutable collection containing all routes registered so far.
     */
    public function routes(): RouteCollection
    {
        return $this->routes;
    }

    /**
     * Registers a GET route.
     *
     * @param array{0: class-string, 1: string}|Closure $action Controller action pair or closure action.
     */
    public function get(string $path, array|Closure $action): PendingRoute
    {
        return $this->add(['GET'], $path, $action);
    }

    /**
     * Registers a HEAD route.
     *
     * @param array{0: class-string, 1: string}|Closure $action Controller action pair or closure action.
     */
    public function head(string $path, array|Closure $action): PendingRoute
    {
        return $this->add(['HEAD'], $path, $action);
    }

    /**
     * Registers a POST route.
     *
     * @param array{0: class-string, 1: string}|Closure $action Controller action pair or closure action.
     */
    public function post(string $path, array|Closure $action): PendingRoute
    {
        return $this->add(['POST'], $path, $action);
    }

    /**
     * Registers a PUT route.
     *
     * @param array{0: class-string, 1: string}|Closure $action Controller action pair or closure action.
     */
    public function put(string $path, array|Closure $action): PendingRoute
    {
        return $this->add(['PUT'], $path, $action);
    }

    /**
     * Registers a PATCH route.
     *
     * @param array{0: class-string, 1: string}|Closure $action Controller action pair or closure action.
     */
    public function patch(string $path, array|Closure $action): PendingRoute
    {
        return $this->add(['PATCH'], $path, $action);
    }

    /**
     * Registers a DELETE route.
     *
     * @param array{0: class-string, 1: string}|Closure $action Controller action pair or closure action.
     */
    public function delete(string $path, array|Closure $action): PendingRoute
    {
        return $this->add(['DELETE'], $path, $action);
    }

    /**
     * Registers an OPTIONS route.
     *
     * @param array{0: class-string, 1: string}|Closure $action Controller action pair or closure action.
     */
    public function options(string $path, array|Closure $action): PendingRoute
    {
        return $this->add(['OPTIONS'], $path, $action);
    }

    /**
     * Registers one route for an explicit set of HTTP methods.
     *
     * @param non-empty-list<string> $methods HTTP methods accepted by the route.
     * @param array{0: class-string, 1: string}|Closure $action Controller action pair or closure action.
     */
    public function match(array $methods, string $path, array|Closure $action): PendingRoute
    {
        return $this->add($methods, $path, $action);
    }

    /**
     * Registers one route for all common HTTP methods.
     *
     * @param array{0: class-string, 1: string}|Closure $action Controller action pair or closure action.
     */
    public function any(string $path, array|Closure $action): PendingRoute
    {
        return $this->add(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $path, $action);
    }

    /**
     * Applies shared route attributes while the callback registers nested routes.
     *
     * @param array{prefix?: string, name?: string, middleware?: string|list<string>, api?: bool} $attributes Group prefix, name prefix, middleware, and API formatting flag.
     */
    public function group(array $attributes, Closure $routes): void
    {
        $this->groups[] = [
            'prefix' => $this->groupPrefix($attributes['prefix'] ?? ''),
            'name' => $this->groupName($attributes['name'] ?? ''),
            'middleware' => $this->groupMiddleware($attributes['middleware'] ?? []),
            'api' => $attributes['api'] ?? false,
        ];

        $routes($this);

        array_pop($this->groups);
    }

    /**
     * Loads a PHP route declaration file with `$router` bound to this router instance.
     */
    public function load(string $path): void
    {
        if (!$this->filesystem->isFile($path)) {
            throw new RouteFileNotFoundException($path);
        }

        $router = $this;

        require $path;
    }

    /**
     * Registers a middleware alias that routes may reference by name.
     */
    public function aliasMiddleware(string $alias, string $middleware): void
    {
        $this->assertMiddlewareName($alias, InvalidRoutingConfigException::middlewareAliasNameIsInvalid(...));
        $this->middlewareAliases[$alias] = $this->middlewareClass($middleware);
    }

    /**
     * Registers a named middleware group that expands to one or more middleware declarations.
     *
     * @param string|list<string> $middleware Middleware alias, class, group name, or list of declarations.
     */
    public function middlewareGroup(string $name, string|array $middleware): void
    {
        $this->assertMiddlewareName($name, InvalidRoutingConfigException::middlewareGroupNameIsInvalid(...));
        $this->middlewareGroups[$name] = $this->expandMiddleware($middleware);
    }

    /**
     * Registers middleware that wraps every matched HTTP route.
     *
     * @param string|list<string> $middleware Middleware alias, class, group name, or list of declarations.
     */
    public function globalMiddleware(string|array $middleware): void
    {
        $this->globalMiddleware = [...$this->globalMiddleware, ...$this->expandMiddleware($middleware)];
    }

    /**
     * Returns global middleware declarations in execution order.
     *
     * @return list<string>
     */
    public function globalMiddlewareList(): array
    {
        return $this->globalMiddleware;
    }

    /**
     * Registers conventional resource routes for a controller.
     *
     * @param list<string>|null $only Resource action names to include, or all actions when null.
     * @param list<string> $except Resource action names to exclude.
     */
    public function resource(string $name, string $controller, ?array $only = null, array $except = [], ?string $parameter = null): void
    {
        $parameter ??= $this->resourceParameter($name);
        $only = $this->normalizeResourceActions($only ?? $this->resourceActions());
        $except = $this->normalizeResourceActions($except);

        foreach (self::RESOURCE_ROUTES as $route) {
            if (!in_array($route['action'], $only, true) || in_array($route['action'], $except, true)) {
                continue;
            }

            $this->add(
                [$route['method']],
                sprintf($route['path'], $name, $parameter),
                [$controller, $route['action']],
            )->name("{$name}.{$route['name']}");
        }
    }

    /**
     * @param non-empty-list<string> $methods
     * @param array{0: string, 1: string}|Closure $action
     */
    private function add(array $methods, string $path, array|Closure $action): PendingRoute
    {
        $route = new Route(
            methods: array_map(static fn(string $method): string => strtoupper($method), $methods),
            path: $this->routePath($path),
            action: $action instanceof Closure ? RouteAction::fromClosure($action) : RouteAction::fromArray($action),
        );

        $route->middleware($this->currentMiddleware());

        if ($this->currentIsApi()) {
            $route->api();
        }

        $this->routes->add($route);

        return new PendingRoute($route, $this->currentName(), $this->routes, fn(string|array $middleware): array => $this->expandMiddleware($middleware));
    }

    private function routePath(string $path): string
    {
        $path = '/' . trim($path, '/');
        $prefix = $this->currentPrefix();

        if ($prefix === '') {
            return $path === '/' ? '/' : rtrim($path, '/');
        }

        $combined = '/' . trim($prefix . '/' . trim($path, '/'), '/');

        return $combined === '/' ? '/' : rtrim($combined, '/');
    }

    private function currentPrefix(): string
    {
        return implode('', array_column($this->groups, 'prefix'));
    }

    private function currentName(): string
    {
        return implode('', array_column($this->groups, 'name'));
    }

    private function currentIsApi(): bool
    {
        foreach ($this->groups as $group) {
            if ($group['api']) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function currentMiddleware(): array
    {
        $middleware = [];

        foreach ($this->groups as $group) {
            $middleware = [...$middleware, ...$group['middleware']];
        }

        return $middleware;
    }

    private function groupPrefix(string $prefix): string
    {
        if ($prefix === '') {
            return '';
        }

        return '/' . trim($prefix, '/');
    }

    private function groupName(string $name): string
    {
        if ($name !== '' && preg_match('/^[A-Za-z0-9_.-]+$/', $name) !== 1) {
            throw InvalidRoutingConfigException::routeGroupNameIsInvalid($name);
        }

        return $name;
    }

    /**
     * @param string|list<string> $middleware
     *
     * @return list<string>
     */
    private function groupMiddleware(string|array $middleware): array
    {
        return $this->expandMiddleware($middleware);
    }

    /**
     * @param string|list<string> $middleware
     *
     * @return list<string>
     */
    private function expandMiddleware(string|array $middleware): array
    {
        $expanded = [];

        foreach ((array) $middleware as $item) {
            if (isset($this->middlewareGroups[$item])) {
                $expanded = [...$expanded, ...$this->middlewareGroups[$item]];

                continue;
            }

            if (isset($this->middlewareAliases[$item])) {
                $expanded[] = $this->middlewareAliases[$item];

                continue;
            }

            $expanded[] = $this->middlewareClass($item);
        }

        return $expanded;
    }

    private function middlewareClass(string $middleware): string
    {
        if (!class_exists($middleware)) {
            throw InvalidRoutingConfigException::middlewareClassDoesNotExist($middleware);
        }

        if (!is_a($middleware, Middleware::class, true)) {
            throw InvalidRoutingConfigException::middlewareClassIsInvalid($middleware);
        }

        return $middleware;
    }

    /**
     * @param callable(string): InvalidRoutingConfigException $exception
     */
    private function assertMiddlewareName(string $name, callable $exception): void
    {
        if (preg_match('/^[A-Za-z][A-Za-z0-9_.:-]*$/', $name) !== 1) {
            throw $exception($name);
        }
    }

    private function resourceParameter(string $name): string
    {
        return str_ends_with($name, 's') ? substr($name, 0, -1) : $name;
    }

    /**
     * @return list<string>
     */
    private function resourceActions(): array
    {
        return array_values(array_unique(array_column(self::RESOURCE_ROUTES, 'action')));
    }

    /**
     * @param list<string> $actions
     *
     * @return list<string>
     */
    private function normalizeResourceActions(array $actions): array
    {
        $allowedActions = $this->resourceActions();
        $normalized = [];

        foreach ($actions as $action) {
            if (!in_array($action, $allowedActions, true)) {
                throw InvalidResourceRouteException::unknownAction($action, $allowedActions);
            }

            if (!in_array($action, $normalized, true)) {
                $normalized[] = $action;
            }
        }

        return $normalized;
    }
}
