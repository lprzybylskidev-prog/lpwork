<?php

declare(strict_types=1);

use App\Modules\Welcome\Routes\RoutesProvider;
use LPWork\Container\Container;
use LPWork\Foundation\Contracts\ServiceProvider;
use LPWork\Http\Exceptions\NotFoundException;
use LPWork\Routing\Contracts\RouteDefinition;
use LPWork\Routing\Providers\RoutesProvider as BaseRoutesProvider;
use LPWork\Routing\Router;
use Tests\support\ApplicationFactory;
use Tests\support\routing\ProviderRouteDefinition;
use Tests\support\routing\TestController;

it('defines the welcome module routes provider', function (): void {
    $provider = new RoutesProvider();

    expect($provider)->toBeInstanceOf(ServiceProvider::class);
});

it('loads route definition classes into the container router', function (): void {
    $container = new Container();
    $container->singleton(Router::class);

    $provider = new class extends BaseRoutesProvider {
        /**
         * @return list<class-string<RouteDefinition>>
         */
        protected function routeDefinitions(): array
        {
            return [
                ProviderRouteDefinition::class,
            ];
        }
    };

    $provider->register($container);

    $router = $container->make(Router::class);

    expect($router)->toBeInstanceOf(Router::class);

    if (!$router instanceof Router) {
        return;
    }

    $match = $router->routes()->match('GET', '/provider-route');

    expect($match->route()->name())->toBe('provider.route')
        ->and($match->route()->action()->controller())->toBe(TestController::class);
});

it('allows route providers without route definitions', function (): void {
    $container = new Container();
    $container->singleton(Router::class);

    $provider = new class extends BaseRoutesProvider {
        /**
         * @return list<class-string<RouteDefinition>>
         */
        protected function routeDefinitions(): array
        {
            return [];
        }
    };

    $provider->register($container);

    $router = $container->make(Router::class);

    expect($router)->toBeInstanceOf(Router::class);
});

it('loads the default welcome module route', function (): void {
    $app = ApplicationFactory::create();
    $container = $app->container();

    $container->singleton(Router::class);

    $provider = new RoutesProvider();
    $provider->register($container);

    $router = $container->make(Router::class);

    expect($router)->toBeInstanceOf(Router::class);

    if (!$router instanceof Router) {
        return;
    }

    $match = $router->routes()->match('GET', '/');
    expect($match->route()->name())->toBe('home');

    expect(fn() => $router->routes()->match('GET', '/debugbar-demo'))
        ->toThrow(NotFoundException::class);
});
