<?php

declare(strict_types=1);

namespace LPWork\Routing\Providers;

use LPWork\Config\Config;
use LPWork\Config\Exceptions\MissingVariableException;
use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Foundation\Application;
use LPWork\Foundation\ServiceProvider;
use LPWork\Routing\Commands\RouteCacheCommand;
use LPWork\Routing\Commands\RouteClearCommand;
use LPWork\Routing\RouteCache;
use LPWork\Routing\RouteCollection;
use LPWork\Routing\RouteCompiledCache;
use LPWork\Routing\RouteMiddlewareRegistrar;
use LPWork\Routing\Router;
use LPWork\Security\SignedUrl;
use LPWork\Url\Url;
use LPWork\Url\UrlGenerator;

/**
 * Registers routing service provider services with the framework container.
 */
final class RoutingServiceProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $container->singleton(Router::class);
        $container->singleton(RouteMiddlewareRegistrar::class);

        $router = $container->make(Router::class);
        $middlewareRegistrar = $container->make(RouteMiddlewareRegistrar::class);

        if ($router instanceof Router && $middlewareRegistrar instanceof RouteMiddlewareRegistrar) {
            $middlewareRegistrar->register($router, $this->middlewareConfig());
        }

        $container->singleton(RouteCollection::class, static function (Container $container): RouteCollection {
            $router = $container->make(Router::class);

            if ($router instanceof Router) {
                return $router->routes();
            }

            return new RouteCollection();
        });

        $container->singleton(UrlGenerator::class, static function (Container $container): UrlGenerator {
            $routes = $container->make(RouteCollection::class);

            if (!$routes instanceof RouteCollection) {
                $routes = new RouteCollection();
            }

            try {
                $signedUrl = $container->make(SignedUrl::class);
            } catch (CannotResolveDependencyException) {
                $signedUrl = null;
            }

            return new UrlGenerator(
                routes: $routes,
                baseUrl: Config::getString('app.url'),
                signedUrl: $signedUrl instanceof SignedUrl ? $signedUrl : null,
            );
        });

        $urlGenerator = $container->make(UrlGenerator::class);

        if ($urlGenerator instanceof UrlGenerator) {
            Url::setGenerator($urlGenerator);
        }

        $container->singleton(RouteCache::class, static function (Container $container): RouteCache {
            $app = $container->make(Application::class);

            if (!$app instanceof Application) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Application::class);
            }

            return new RouteCache($app->basePath());
        });
        $container->singleton(RouteCompiledCache::class);
        $container->singleton(RouteCacheCommand::class);
        $container->singleton(RouteClearCommand::class);
        $this->registerCompiledCaches($container, [RouteCompiledCache::class]);

        $this->registerCommands($container, [
            RouteCacheCommand::class,
            RouteClearCommand::class,
        ]);
    }

    /**
     * @return array<array-key, mixed>
     */
    private function middlewareConfig(): array
    {
        try {
            return Config::getArray('routing.middleware');
        } catch (MissingVariableException) {
            return [];
        }
    }
}
