<?php

declare(strict_types=1);

use LPWork\ErrorHandling\Routes\ErrorRoutes;
use LPWork\Http\Exceptions\MethodNotAllowedException;
use LPWork\Responses\HttpResponse;
use LPWork\Routing\Router;
use Tests\support\errorhandling\ProductionErrorRouteRendererFactory;

it('renders production exceptions through the built in error route', function (): void {
    $router = new Router();
    new ErrorRoutes()->register($router);

    $response = ProductionErrorRouteRendererFactory::make($router)->render(new RuntimeException('Secret production failure'));

    expect($response->statusCode())->toBe(500)
        ->and($response->body())->toContain('Production response')
        ->and($response->body())->toContain('/assets/lpwork-logo.svg?v=')
        ->and($response->body())->toContain('/favicon.svg?v=')
        ->and($response->body())->toContain('lp-ui-status-page--error')
        ->and($response->body())->toContain('class="lp-ui-status-code">500</p>')
        ->and($response->body())->not->toContain('Secret production failure');
});

it('preserves Http exception status codes and headers while rendering error routes', function (): void {
    $router = new Router();
    new ErrorRoutes()->register($router);

    $response = ProductionErrorRouteRendererFactory::make($router)->render(new MethodNotAllowedException(['GET', 'POST']));

    expect($response->statusCode())->toBe(405)
        ->and($response->header('Allow'))->toBe('GET, POST')
        ->and($response->body())->toContain('Method not allowed')
        ->and($response->body())->toContain('class="lp-ui-status-code">405</p>');
});

it('uses application error routes before the built in route', function (): void {
    $router = new Router();
    $router->get('/error/{code}', static fn(string $code): HttpResponse => HttpResponse::html('Custom app error ' . $code));
    new ErrorRoutes()->register($router);

    $response = ProductionErrorRouteRendererFactory::make($router)->render(new RuntimeException('Failure'));

    expect($response->statusCode())->toBe(500)
        ->and($response->body())->toBe('Custom app error 500');
});

it('falls back to the safe production renderer when the error route cannot render', function (): void {
    $response = ProductionErrorRouteRendererFactory::make(new Router())->render(new RuntimeException('Hidden failure'));

    expect($response->statusCode())->toBe(500)
        ->and($response->body())->toContain('Server error')
        ->and($response->body())->not->toContain('Hidden failure');
});
