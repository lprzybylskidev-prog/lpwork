<?php

declare(strict_types=1);

use LPWork\Kernels\Http\HttpThrottleMiddlewareResolver;
use LPWork\Routing\Route;
use LPWork\Routing\RouteAction;
use LPWork\Routing\RouteMatch;
use LPWork\Throttle\Contracts\ThrottleClock;
use LPWork\Throttle\Contracts\ThrottleStorage;
use LPWork\Throttle\HttpThrottleMiddleware;
use LPWork\Throttle\Storage\InMemoryThrottleStorage;
use LPWork\Throttle\ThrottleConfig;
use LPWork\Throttle\ThrottleLimiter;
use Tests\support\ApplicationFactory;
use Tests\support\routing\TestController;
use Tests\support\throttle\MutableThrottleClock;
use Tests\support\throttle\ThrottleConfigBuilder;

it('resolves HTTP throttle middleware for web and API routes', function (): void {
    $app = ApplicationFactory::create();
    $clock = new MutableThrottleClock();
    $app->container()->instance(ThrottleConfig::class, ThrottleConfigBuilder::config(web: true, api: true));
    $app->container()->instance(ThrottleClock::class, $clock);
    $app->container()->instance(ThrottleStorage::class, new InMemoryThrottleStorage());
    $app->container()->instance(ThrottleLimiter::class, new ThrottleLimiter(new InMemoryThrottleStorage(), $clock));

    $webRoute = new Route(['GET'], '/', RouteAction::fromArray([TestController::class, 'index']));
    $apiRoute = new Route(['GET'], '/api', RouteAction::fromArray([TestController::class, 'index']));
    $apiRoute->api();

    $resolver = new HttpThrottleMiddlewareResolver($app);

    expect($resolver->resolve(new RouteMatch($webRoute))[0])->toBeInstanceOf(HttpThrottleMiddleware::class)
        ->and($resolver->resolve(new RouteMatch($apiRoute))[0])->toBeInstanceOf(HttpThrottleMiddleware::class);
});

it('skips HTTP throttle middleware when throttle services are not configured', function (): void {
    $route = new Route(['GET'], '/', RouteAction::fromArray([TestController::class, 'index']));

    expect(new HttpThrottleMiddlewareResolver(ApplicationFactory::create())->resolve(new RouteMatch($route)))->toBe([]);
});
