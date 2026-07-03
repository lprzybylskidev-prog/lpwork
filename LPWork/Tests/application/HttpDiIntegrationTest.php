<?php

declare(strict_types=1);

use LPWork\Routing\Router;
use Tests\support\routing\ContainerController;
use Tests\support\routing\InjectedMessage;
use Tests\support\testing\ApplicationTestHarness;
use Tests\support\testing\Http\HttpTestClient;

beforeEach(function (): void {
    ApplicationTestHarness::resetFrameworkState();
});

afterEach(function (): void {
    ApplicationTestHarness::resetFrameworkState();
});

afterAll(function (): void {
    ApplicationTestHarness::removeDirectories();
});

it('loads routes and controller dependencies through the bootstrapped application container', function (): void {
    $harness = ApplicationTestHarness::fromProjectDefaults();
    $app = $harness->bootstrap();
    $app->container()->instance(InjectedMessage::class, new InjectedMessage('from-container'));

    $router = $app->container()->make(Router::class);

    expect($router)->toBeInstanceOf(Router::class);

    if ($router instanceof Router) {
        $router->get('/di-container-route', [ContainerController::class, 'index'])->name('di.container');

        HttpTestClient::forApplication($app)
            ->get('/di-container-route')
            ->assertOk()
            ->assertBody('GET /di-container-route from-container');

        expect($router->routes()->named('di.container')->path())->toBe('/di-container-route');
    }
});
