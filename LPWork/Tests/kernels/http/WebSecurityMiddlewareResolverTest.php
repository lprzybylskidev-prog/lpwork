<?php

declare(strict_types=1);

use LPWork\Kernels\Http\WebSecurityMiddlewareResolver;
use LPWork\Middleware\SessionMiddleware;
use LPWork\Routing\Route;
use LPWork\Routing\RouteAction;
use LPWork\Routing\RouteMatch;
use LPWork\Security\Csrf\CsrfConfig;
use LPWork\Security\Csrf\CsrfMiddleware;
use LPWork\Security\Csrf\CsrfTokenManager;
use LPWork\Security\SecurityConfig;
use Tests\support\ApplicationFactory;
use Tests\support\routing\TestController;
use Tests\support\security\SecurityConfigs;
use Tests\support\session\InMemorySessionDriver;

it('resolves session and CSRF middleware for configured web routes', function (): void {
    $app = ApplicationFactory::create();
    $config = SecurityConfigs::http(csrfEnabled: true);
    $app->container()->instance(SecurityConfig::class, $config);
    $app->container()->instance(CsrfConfig::class, $config->csrf());
    $app->container()->instance(CsrfTokenManager::class, new CsrfTokenManager($config->csrf()));
    $app->container()->instance(SessionMiddleware::class, new SessionMiddleware(new InMemorySessionDriver()));

    $middleware = new WebSecurityMiddlewareResolver($app)->resolve(new RouteMatch(new Route(
        methods: ['POST'],
        path: '/profile',
        action: RouteAction::fromArray([TestController::class, 'inputTitle']),
    )));

    expect($middleware)->toHaveCount(2)
        ->and($middleware[0])->toBeInstanceOf(SessionMiddleware::class)
        ->and($middleware[1])->toBeInstanceOf(CsrfMiddleware::class);
});

it('does not resolve CSRF middleware for API routes or disabled CSRF', function (): void {
    $apiApp = ApplicationFactory::create();
    $config = SecurityConfigs::http(csrfEnabled: true);
    $apiApp->container()->instance(SecurityConfig::class, $config);

    $apiRoute = new Route(
        methods: ['POST'],
        path: '/api/profile',
        action: RouteAction::fromArray([TestController::class, 'apiStore']),
    );
    $apiRoute->api();

    $disabledApp = ApplicationFactory::create();
    $disabledApp->container()->instance(SecurityConfig::class, SecurityConfigs::http(csrfEnabled: false));

    $webRoute = new Route(
        methods: ['POST'],
        path: '/profile',
        action: RouteAction::fromArray([TestController::class, 'inputTitle']),
    );

    expect(new WebSecurityMiddlewareResolver($apiApp)->resolve(new RouteMatch($apiRoute)))->toBe([])
        ->and(new WebSecurityMiddlewareResolver($disabledApp)->resolve(new RouteMatch($webRoute)))->toBe([]);
});
