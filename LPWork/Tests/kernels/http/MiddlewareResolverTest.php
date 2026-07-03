<?php

declare(strict_types=1);

use LPWork\Kernels\Http\MiddlewareResolver;
use LPWork\Middleware\Exceptions\InvalidMiddlewareException;
use LPWork\Middleware\Exceptions\MiddlewareNotFoundException;
use LPWork\Routing\Route;
use LPWork\Routing\RouteAction;
use LPWork\Routing\RouteMatch;
use LPWork\Routing\Router;
use Tests\support\ApplicationFactory;
use Tests\support\middleware\ContainerMiddleware;
use Tests\support\middleware\InjectedHeader;
use Tests\support\middleware\NotMiddleware;
use Tests\support\routing\TestController;

it('resolves route middleware through the application container', function (): void {
    $app = ApplicationFactory::create();
    $app->container()->instance(InjectedHeader::class, new InjectedHeader('from-resolver'));

    $router = new Router();
    $router->get('/profile', [TestController::class, 'index'])
        ->middleware(ContainerMiddleware::class);
    $match = $router->routes()->match('GET', '/profile');

    $middleware = new MiddlewareResolver($app)->resolve($match);

    expect($middleware)->toHaveCount(1)
        ->and($middleware[0])->toBeInstanceOf(ContainerMiddleware::class);
});

it('rejects route middleware that does not implement the middleware contract', function (): void {
    $route = new Route(['GET'], '/invalid', RouteAction::fromArray([TestController::class, 'index']));
    $route->middleware(NotMiddleware::class);
    $match = new RouteMatch($route);

    expect(fn() => new MiddlewareResolver(ApplicationFactory::create())->resolve($match))
        ->toThrow(InvalidMiddlewareException::class);
});

it('rejects route middleware that does not exist', function (): void {
    $route = new Route(['GET'], '/missing', RouteAction::fromArray([TestController::class, 'index']));
    $route->middleware('Tests\\support\\middleware\\MissingMiddleware');
    $match = new RouteMatch($route);

    expect(fn() => new MiddlewareResolver(ApplicationFactory::create())->resolve($match))
        ->toThrow(MiddlewareNotFoundException::class);
});
