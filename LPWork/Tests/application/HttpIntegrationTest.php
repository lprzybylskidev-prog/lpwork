<?php

declare(strict_types=1);

use LPWork\Foundation\FrameworkMetadata;
use LPWork\Routing\Router;
use Tests\support\middleware\FirstMiddleware;
use Tests\support\testing\ApplicationTestHarness;
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

it('exercises application HTTP behavior through the real test client lifecycle', function (): void {
    $harness = ApplicationTestHarness::fromProjectDefaults()
        ->setEnvValue('SESSION_DRIVER', 'memory')
        ->setEnvValue('SECURITY_CSRF_ENABLED', true);

    $app = $harness->bootstrap();
    $router = $app->container()->make(Router::class);

    expect($router)->toBeInstanceOf(Router::class);

    if (!$router instanceof Router) {
        return;
    }

    $router->get('/app-http', [ApplicationHttpIntegrationController::class, 'index'])
        ->middleware(FirstMiddleware::class)
        ->name('app.http');
    $router->get('/redirect-start', [ApplicationHttpIntegrationController::class, 'redirectWithCookieAndSession']);
    $router->get('/dashboard', [ApplicationHttpIntegrationController::class, 'dashboard']);
    $router->post('/api/entries', [ApplicationHttpIntegrationController::class, 'storeJson'])->api();
    $router->get('/csrf-form', [ApplicationHttpIntegrationController::class, 'csrfForm']);
    $router->post('/csrf-submit', [ApplicationHttpIntegrationController::class, 'csrfSubmit']);
    $router->get('/view', [ApplicationHttpIntegrationController::class, 'view']);
    $router->get('/explode', [ApplicationHttpIntegrationController::class, 'fail']);

    $client = HttpTestClient::forApplication($app)
        ->withSession();

    $client->get('/app-http')
        ->assertOk()
        ->assertBody('GET /app-http|first')
        ->assertHeader('X-First-Middleware', 'passed');

    $client->get('/redirect-start')
        ->assertRedirect('/dashboard')
        ->assertCookie('visited', 'yes')
        ->assertSessionHas('dashboard_message', 'from-session');

    $client->get('/dashboard')
        ->assertOk()
        ->assertJsonPath('visited', 'yes')
        ->assertJsonPath('message', 'from-session');

    $client->postJson('/api/entries', [
        'title' => 'JSON entry',
        'nested' => ['published' => true],
    ])
        ->assertCreated()
        ->assertJsonFragment([
            'title' => 'JSON entry',
            'nested' => ['published' => true],
        ]);

    $csrfToken = $client->get('/csrf-form')
        ->assertOk()
        ->body();

    expect($csrfToken)->not->toBe('');

    $client->post('/csrf-submit', ['name' => 'accepted', '_token' => $csrfToken])
        ->assertRedirect('/csrf-form')
        ->assertSessionHas('csrf_result', 'accepted');

    $client->post('/csrf-submit', ['name' => 'rejected'])
        ->assertStatus(403)
        ->assertSee('403');

    $harness->writeFile('resources/views/integration/page.php', '<h1>Hello <?= $view->e($name) ?></h1>');

    $client->get('/view')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
        ->assertBody('<h1>Hello Ada</h1>');

    $client->get('/explode', headers: ['Accept' => 'application/json'])
        ->assertStatus(500)
        ->assertJsonPath('error.status', 500)
        ->assertJsonPath('error.message', 'Internal Server Error');
});

it('renders the localized welcome page through the default home route', function (): void {
    $harness = ApplicationTestHarness::fromProjectDefaults()
        ->setEnvValue('APP_LANG', 'pl_PL')
        ->setEnvValue('APP_ENV', 'production')
        ->writeFile('public/build/manifest.json', json_encode([
            'App/Modules/Welcome/resources/frontend/app.ts' => [
                'file' => 'assets/welcome/app-test.js',
                'css' => ['assets/welcome/app-test.css'],
            ],
        ], JSON_THROW_ON_ERROR))
        ->writeFile('public/build/assets/welcome/app-test.js', 'console.log("welcome");')
        ->writeFile('public/build/assets/welcome/app-test.css', '.welcome {}');

    HttpTestClient::forApplication($harness->bootstrap())
        ->get('/')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
        ->assertSee('<link rel="stylesheet" href="/build/assets/welcome/app-test.css">')
        ->assertSee('<script type="module" src="/build/assets/welcome/app-test.js"></script>')
        ->assertDontSee('@vite/client')
        ->assertSee('Autorski framework PHP')
        ->assertSee('Moduły frameworka')
        ->assertSee('LPWork ' . FrameworkMetadata::VERSION)
        ->assertSee('34 moduły')
        ->assertSee('Backendowy zestaw modułów')
        ->assertSee('Błędy')
        ->assertSee('Własny renderer debug')
        ->assertDontSee('Whoops')
        ->assertDontSee('Po co istnieje')
        ->assertDontSee('Aplikacja działa')
        ->assertDontSee('Domyślna trasa')
        ->assertDontSee('public/index.php -&gt; kernel -&gt; response');
});
