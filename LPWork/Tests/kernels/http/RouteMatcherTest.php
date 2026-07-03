<?php

declare(strict_types=1);

use LPWork\Kernels\Http\RouteMatcher;
use LPWork\Requests\HttpRequest;
use LPWork\Routing\Router;
use Tests\support\routing\TestController;

it('matches requests against router routes', function (): void {
    $router = new Router();
    $route = $router->get('/posts/{id}', [TestController::class, 'show'])->route();

    $match = new RouteMatcher($router)->match(HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/posts/15?preview=1',
    ]));

    expect($match->route())->toBe($route)
        ->and($match->parameters())->toBe(['id' => '15']);
});

it('matches spoofed Http methods from request input', function (): void {
    $router = new Router();
    $route = $router->put('/posts/{id}', [TestController::class, 'update'])->route();

    $match = new RouteMatcher($router)->match(HttpRequest::fromArrays(
        server: [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/posts/15',
        ],
        input: ['_method' => 'PUT'],
    ));

    expect($match->route())->toBe($route);
});
