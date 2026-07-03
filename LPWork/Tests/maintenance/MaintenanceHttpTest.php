<?php

declare(strict_types=1);

use LPWork\Frontend\FrameworkPageRenderer;
use LPWork\Kernels\Http\ControllerDispatcher;
use LPWork\Maintenance\MaintenanceState;
use LPWork\Responses\HttpResponse;
use LPWork\Routing\Router;
use Tests\support\ApplicationFactory;
use Tests\support\testing\ApplicationTestHarness;
use Tests\support\testing\Cli\CliTestClient;
use Tests\support\testing\Http\ApplicationHttpIntegrationController;
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

it('renders the built in maintenance page', function (): void {
    $harness = ApplicationTestHarness::fromProjectDefaults();
    $app = $harness->bootstrap();

    HttpTestClient::forApplication($app)
        ->get('/maintenance')
        ->assertStatus(503)
        ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
        ->assertSee('Maintenance mode')
        ->assertSee('class="lp-ui-body"')
        ->assertSee('lp-ui-status-page--maintenance')
        ->assertSee('class="lp-ui-status-main"')
        ->assertSee('Service unavailable')
        ->assertDontSee('class="lp-ui-status-details"')
        ->assertDontSee('Mode')
        ->assertSee('/assets/lpwork-logo.svg?v=')
        ->assertSee('/favicon.svg?v=')
        ->assertSee('class="lp-ui-status-code">503</p>');
});

it('returns maintenance responses before route matching while active', function (): void {
    $harness = ApplicationTestHarness::fromProjectDefaults();
    $app = $harness->bootstrap(['lpwork', 'maintenance:down']);

    CliTestClient::forApplication($app)
        ->command('maintenance:down', '--retry=120')
        ->assertSuccessful();

    HttpTestClient::forApplication($app)
        ->get('/missing-page')
        ->assertStatus(503)
        ->assertHeader('Retry-After', '120')
        ->assertSee('temporarily unavailable while maintenance mode is active')
        ->assertSee('/assets/lpwork-logo.svg?v=')
        ->assertSee('/favicon.svg?v=');
});

it('renders an application maintenance route when configured', function (): void {
    $router = new Router();
    $router->get('/custom-maintenance', static fn(): HttpResponse => HttpResponse::html('custom maintenance route'));
    $renderer = new LPWork\Maintenance\MaintenancePageRenderer(
        pages: new FrameworkPageRenderer(),
        router: $router,
        dispatcher: new ControllerDispatcher(ApplicationFactory::create()),
        route: '/custom-maintenance',
    );

    $response = $renderer->render(MaintenanceState::active('60'));

    expect($response->statusCode())->toBe(503)
        ->and($response->header('Retry-After'))->toBe('60')
        ->and($response->body())->toBe('custom maintenance route');
});

it('allows normal routing when maintenance mode is inactive', function (): void {
    $harness = ApplicationTestHarness::fromProjectDefaults();
    $app = $harness->bootstrap();
    $router = $app->container()->make(Router::class);

    expect($router)->toBeInstanceOf(Router::class);

    if (!$router instanceof Router) {
        return;
    }

    $router->get('/available', [ApplicationHttpIntegrationController::class, 'index']);

    HttpTestClient::forApplication($app)
        ->get('/available')
        ->assertOk()
        ->assertBody('GET /available');
});
