<?php

declare(strict_types=1);

use LPWork\Routing\Router;
use LPWork\Security\HmacSigner;
use LPWork\Security\Http\ValidateSignedUrlMiddleware;
use LPWork\Security\SignedUrl;
use LPWork\Security\SignedUrlValidator;
use LPWork\Url\UrlGenerator;
use Tests\support\ApplicationFactory;
use Tests\support\queue\MutableClock;
use Tests\support\routing\TestController;
use Tests\support\testing\Http\HttpTestClient;
use Tests\support\testing\Security\TestApplicationKeys;

it('allows requests with valid signed URLs', function (): void {
    $app = ApplicationFactory::create();
    $clock = new MutableClock(1000);
    $signed = new SignedUrl(new HmacSigner(TestApplicationKeys::key()), $clock);

    $app->container()->instance(SignedUrlValidator::class, new SignedUrlValidator($signed));

    $router = new Router();
    $route = $router->get('/download/{file}', [TestController::class, 'show']);
    $route->name('download.show');
    $route->middleware(ValidateSignedUrlMiddleware::class);

    $url = new UrlGenerator($router->routes(), signedUrl: $signed)
        ->signedRoute('download.show', ['file' => 'report.pdf'], absolute: false);

    HttpTestClient::forApplication($app, $router)
        ->get($url)
        ->assertOk()
        ->assertSee('GET /download/report.pdf report.pdf');
});

it('rejects tampered signed URLs', function (): void {
    $app = ApplicationFactory::create();
    $signed = new SignedUrl(new HmacSigner(TestApplicationKeys::key()));

    $app->container()->instance(SignedUrlValidator::class, new SignedUrlValidator($signed));

    $router = new Router();
    $route = $router->get('/download/{file}', [TestController::class, 'show']);
    $route->name('download.show');
    $route->middleware(ValidateSignedUrlMiddleware::class);

    $url = new UrlGenerator($router->routes(), signedUrl: $signed)
        ->signedRoute('download.show', ['file' => 'report.pdf'], absolute: false);

    HttpTestClient::forApplication($app, $router)
        ->get(str_replace('report.pdf', 'secret.pdf', $url))
        ->assertStatus(403);
});

it('rejects expired temporary signed URLs', function (): void {
    $app = ApplicationFactory::create();
    $clock = new MutableClock(1000);
    $signed = new SignedUrl(new HmacSigner(TestApplicationKeys::key()), $clock);

    $app->container()->instance(SignedUrlValidator::class, new SignedUrlValidator($signed));

    $router = new Router();
    $route = $router->get('/download/{file}', [TestController::class, 'show']);
    $route->name('download.show');
    $route->middleware(ValidateSignedUrlMiddleware::class);

    $url = new UrlGenerator($router->routes(), signedUrl: $signed)
        ->temporarySignedRoute('download.show', 1005, ['file' => 'report.pdf'], absolute: false);

    $clock->travel(6);

    HttpTestClient::forApplication($app, $router)
        ->get($url)
        ->assertStatus(403);
});
