<?php

declare(strict_types=1);

namespace LPWork\Routing;

use LPWork\Routing\Exceptions\InvalidRoutingConfigException;

/**
 * Represents the route middleware registrar framework component.
 */
final readonly class RouteMiddlewareRegistrar
{
    /**
     * @param array{global?: mixed, aliases?: mixed, groups?: mixed} $config
     */
    public function register(Router $router, array $config): void
    {
        foreach ($this->aliases($config['aliases'] ?? []) as $alias => $middleware) {
            $router->aliasMiddleware($alias, $middleware);
        }

        foreach ($this->groups($config['groups'] ?? []) as $name => $middleware) {
            $router->middlewareGroup($name, $middleware);
        }

        $router->globalMiddleware($this->globalMiddleware($config['global'] ?? []));
    }

    /**
     * @return array<string, string>
     */
    private function aliases(mixed $aliases): array
    {
        if (!is_array($aliases)) {
            throw InvalidRoutingConfigException::middlewareAliasesMustBeMap();
        }

        $normalized = [];

        foreach ($aliases as $alias => $middleware) {
            if (!is_string($alias) || !is_string($middleware)) {
                throw InvalidRoutingConfigException::middlewareAliasesMustBeMap();
            }

            $normalized[$alias] = $middleware;
        }

        return $normalized;
    }

    /**
     * @return array<string, string|list<string>>
     */
    private function groups(mixed $groups): array
    {
        if (!is_array($groups)) {
            throw InvalidRoutingConfigException::middlewareGroupsMustBeMap();
        }

        $normalized = [];

        foreach ($groups as $name => $middleware) {
            if (!is_string($name)) {
                throw InvalidRoutingConfigException::middlewareGroupsMustBeMap();
            }

            $normalized[$name] = $this->middlewareList($name, $middleware);
        }

        return $normalized;
    }

    /**
     * @return string|list<string>
     */
    private function middlewareList(string $group, mixed $middleware): string|array
    {
        if (is_string($middleware)) {
            return $middleware;
        }

        if (!is_array($middleware)) {
            throw InvalidRoutingConfigException::middlewareGroupsMustBeMap();
        }

        $normalized = [];

        foreach ($middleware as $item) {
            if (!is_string($item)) {
                throw InvalidRoutingConfigException::middlewareGroupMustContainStrings($group);
            }

            $normalized[] = $item;
        }

        return $normalized;
    }

    /**
     * @return list<string>
     */
    private function globalMiddleware(mixed $middleware): array
    {
        if (!is_array($middleware)) {
            throw InvalidRoutingConfigException::globalMiddlewareMustBeList();
        }

        $normalized = [];

        foreach ($middleware as $item) {
            if (!is_string($item)) {
                throw InvalidRoutingConfigException::globalMiddlewareMustBeList();
            }

            $normalized[] = $item;
        }

        return $normalized;
    }
}
