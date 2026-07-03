<?php

declare(strict_types=1);

use LPWork\Kernels\Http\HttpResponseFormatResolver;
use LPWork\Requests\HttpRequest;
use LPWork\Responses\Enums\ResponseFormat;
use LPWork\Routing\Route;
use LPWork\Routing\RouteAction;
use LPWork\Routing\RouteMatch;
use Tests\support\routing\TestController;

it('resolves HTML for ordinary web requests', function (): void {
    $request = HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/articles',
        'HTTP_ACCEPT' => 'text/html',
    ]);

    expect(new HttpResponseFormatResolver()->resolve($request))->toBe(ResponseFormat::Html);
});

it('resolves JSON for API routes and JSON accept headers', function (): void {
    $resolver = new HttpResponseFormatResolver();
    $request = HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/articles',
        'HTTP_ACCEPT' => 'text/html',
    ]);
    $jsonRequest = HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/articles',
        'HTTP_ACCEPT' => 'application/vnd.api+json; version=1',
    ]);
    $route = new Route(['GET'], '/articles', RouteAction::fromArray([TestController::class, 'index']));
    $route->api();

    expect($resolver->resolve($request, new RouteMatch($route)))->toBe(ResponseFormat::Json)
        ->and($resolver->resolve($jsonRequest))->toBe(ResponseFormat::Json);
});

it('uses negotiated accept preferences for web routes and forces JSON for API routes', function (): void {
    $resolver = new HttpResponseFormatResolver();
    $request = HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/articles',
        'HTTP_ACCEPT' => 'application/json;q=0.2, text/html;q=0.9',
    ]);
    $route = new Route(['GET'], '/articles', RouteAction::fromArray([TestController::class, 'index']));
    $apiRoute = new Route(['GET'], '/articles', RouteAction::fromArray([TestController::class, 'index']));
    $apiRoute->api();

    expect($resolver->resolve($request, new RouteMatch($route)))->toBe(ResponseFormat::Html)
        ->and($resolver->resolve($request, new RouteMatch($apiRoute)))->toBe(ResponseFormat::Json);
});
