<?php

declare(strict_types=1);

use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;
use LPWork\Routing\Router;
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

it('executes HTTP requests through the real HTTP kernel without browser emission', function (): void {
    $app = ApplicationTestHarness::create()->application();
    $router = new Router();
    $router->get('/health', static fn(): HttpResponse => HttpResponse::text('healthy'));

    $response = HttpTestClient::forApplication($app, $router)->send(HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/health',
    ]));

    $response
        ->assertOk()
        ->assertBody('healthy')
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
});

it('uses the application router from the container when no router is provided', function (): void {
    $harness = ApplicationTestHarness::create();
    $router = new Router();
    $router->get('/container-route', static fn(): HttpResponse => HttpResponse::text('from container'));
    $harness->container()->instance(Router::class, $router);

    $response = HttpTestClient::forApplication($harness->application())->send(HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/container-route',
    ]));

    $response
        ->assertOk()
        ->assertBody('from container');
});

it('builds common method requests before sending them through the kernel', function (): void {
    $app = ApplicationTestHarness::create()->application();
    $router = new Router();
    $router->post('/articles', static fn(LPWork\Requests\HttpRequest $request): HttpResponse => HttpResponse::text(
        $request->method() . ' ' . $request->string('title') . ' ' . ($request->header('Accept') ?? ''),
    ));

    $response = HttpTestClient::forApplication($app, $router)
        ->post('/articles', ['title' => 'Draft'], ['Accept' => 'application/json']);

    $response
        ->assertOk()
        ->assertBody('POST Draft application/json');
});

it('sends fully customized requests through the kernel', function (): void {
    $app = ApplicationTestHarness::create()->application();
    $router = new Router();
    $router->put('/articles/{id}', static function (LPWork\Requests\HttpRequest $request, string $id): HttpResponse {
        $session = $request->cookie('session');

        if (!is_string($session)) {
            return HttpResponse::text('invalid session cookie', 500);
        }

        return HttpResponse::text(
            $id . ' ' . $request->queryString('preview') . ' ' . $session . ' ' . $request->ip(),
        );
    });

    $response = HttpTestClient::forApplication($app, $router)->request(
        method: 'PUT',
        uri: '/articles/15?preview=0',
        query: ['preview' => '1'],
        input: ['title' => 'Updated'],
        headers: ['Accept' => 'text/plain'],
        cookies: ['session' => 'abc'],
        server: ['REMOTE_ADDR' => '10.0.0.10'],
    );

    $response
        ->assertOk()
        ->assertBody('15 1 abc 10.0.0.10');
});

it('sends JSON requests through the kernel', function (): void {
    $app = ApplicationTestHarness::create()->application();
    $router = new Router();
    $router->post('/api/articles', static fn(LPWork\Requests\HttpRequest $request): HttpResponse => HttpResponse::json([
        'body' => $request->body(),
        'content_type' => $request->header('Content-Type'),
        'accept' => $request->header('Accept'),
    ]))->api();

    $response = HttpTestClient::forApplication($app, $router)
        ->postJson('/api/articles', ['title' => 'API article']);

    $response
        ->assertOk()
        ->assertExactJson([
            'body' => '{"title":"API article"}',
            'content_type' => 'application/json',
            'accept' => 'application/json',
        ]);
});

it('persists response cookies across sequential requests', function (): void {
    $app = ApplicationTestHarness::create()->application();
    $router = new Router();
    $router->get('/login', static fn(): HttpResponse => HttpResponse::text('logged in')->withCookie(new LPWork\Http\Cookie('session', 'abc')));
    $router->get('/dashboard', static function (LPWork\Requests\HttpRequest $request): HttpResponse {
        $value = $request->cookie('session', 'missing');

        return HttpResponse::text(is_string($value) ? $value : 'missing');
    });
    $client = HttpTestClient::forApplication($app, $router);

    $client->get('/login')->assertCookie('session', 'abc');

    $client->get('/dashboard')->assertBody('abc');
});

it('allows explicit cookie overrides and resets client cookies', function (): void {
    $app = ApplicationTestHarness::create()->application();
    $router = new Router();
    $router->get('/profile', static function (LPWork\Requests\HttpRequest $request): HttpResponse {
        $value = $request->cookie('theme', 'missing');

        return HttpResponse::text(is_string($value) ? $value : 'missing');
    });
    $client = HttpTestClient::forApplication($app, $router)
        ->withCookie('theme', 'dark');

    $client->get('/profile')->assertBody('dark');
    $client->request('GET', '/profile', cookies: ['theme' => 'light'])->assertBody('light');
    $client->get('/profile')->assertBody('dark');
    $client->clearCookies();
    $client->get('/profile')->assertBody('missing');
});

it('persists session data through real session middleware', function (): void {
    $app = ApplicationTestHarness::create()->application();
    $router = new Router();
    $router->get('/profile', static function (LPWork\Requests\HttpRequest $request): HttpResponse {
        $request->session()->put('visited', true);
        $userId = $request->session()->get('user_id');

        return HttpResponse::text(is_scalar($userId) ? (string) $userId : '');
    });

    $client = HttpTestClient::forApplication($app, $router)
        ->withSession(['user_id' => 15]);

    $client->get('/profile')
        ->assertOk()
        ->assertBody('15')
        ->assertSessionHas('visited', true);

    $client->session()
        ->assertStarted(1)
        ->assertSaved(1)
        ->assertHas('visited', true);
});

it('supports old input errors and lifecycle assertions through test session storage', function (): void {
    $app = ApplicationTestHarness::create()->application();
    $router = new Router();
    $router->post('/submit', static function (LPWork\Requests\HttpRequest $request): HttpResponse {
        $request->session()->flashInput(['title' => 'Draft']);
        $request->session()->flashErrors(['title' => 'Required']);
        $request->session()->regenerate();

        return HttpResponse::redirect('/form');
    });

    $client = HttpTestClient::forApplication($app, $router)
        ->withSession();

    $client->post('/submit')
        ->assertRedirect('/form')
        ->assertOldInput('title', 'Draft')
        ->assertSessionError('title', 'Required');

    $client->session()
        ->assertRegenerated()
        ->assertSaved(1)
        ->assertOldInput('title', 'Draft')
        ->assertError('title', 'Required');
});

it('captures formatted exception responses from the kernel', function (): void {
    $app = ApplicationTestHarness::create()->application();
    $router = new Router();
    $client = HttpTestClient::forApplication($app, $router);

    $response = $client->send(HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/missing',
    ]));

    $response
        ->assertNotFound()
        ->assertSee('class="lp-ui-status-code">404</p>');

    expect($client->emittedResponses())->toBe(1);
});
