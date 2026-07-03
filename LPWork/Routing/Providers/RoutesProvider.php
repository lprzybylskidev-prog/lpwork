<?php

declare(strict_types=1);

namespace LPWork\Routing\Providers;

use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Foundation\Application;
use LPWork\Foundation\ServiceProvider;
use LPWork\Routing\Contracts\RouteDefinition;
use LPWork\Routing\RouteCache;
use LPWork\Routing\Router;

/**
 * Registers routes provider services with the framework container.
 */
abstract class RoutesProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $router = $container->make(Router::class);

        if (!$router instanceof Router) {
            return;
        }

        $cache = $this->routeCache($container);

        if ($cache !== null && $cache->exists()) {
            if ($router->routes()->isEmpty()) {
                $cache->loadInto($router->routes());
            }

            return;
        }

        foreach ($this->definitions($container) as $definition) {
            $definition->register($router);
        }
    }

    /**
     * @return list<class-string<RouteDefinition>>
     */
    abstract protected function routeDefinitions(): array;

    /**
     * @return list<RouteDefinition>
     */
    private function definitions(Container $container): array
    {
        $definitions = [];

        foreach ($this->routeDefinitions() as $definition) {
            $resolved = $container->make($definition);

            if ($resolved instanceof RouteDefinition) {
                $definitions[] = $resolved;
            }
        }

        return $definitions;
    }

    private function routeCache(Container $container): ?RouteCache
    {
        try {
            $app = $container->make(Application::class);
        } catch (CannotResolveDependencyException) {
            return null;
        }

        if (!$app instanceof Application) {
            throw CannotResolveDependencyException::factoryDidNotReturnObject(Application::class);
        }

        return new RouteCache($app->basePath());
    }
}
