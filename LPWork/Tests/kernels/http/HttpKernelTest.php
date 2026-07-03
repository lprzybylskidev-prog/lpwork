<?php

declare(strict_types=1);

use LPWork\Bootstrap\Bootstrap;
use LPWork\Config\Config;
use LPWork\DebugBar\DebugBarRenderer;
use LPWork\DebugBar\DebugBarRequestStore;
use LPWork\DebugBar\DebugBarResponseInjector;
use LPWork\Emitters\HttpEmitter;
use LPWork\Environment\Environment;
use LPWork\ErrorHandling\Context\HttpRequestDebugContextProvider;
use LPWork\ErrorHandling\Context\MiddlewareDebugContextProvider;
use LPWork\ErrorHandling\Context\RouteDebugContextProvider;
use LPWork\ErrorHandling\Context\SessionDebugContextProvider;
use LPWork\ErrorHandling\Contracts\ExceptionReporter;
use LPWork\ErrorHandling\Contracts\HttpExceptionRenderer;
use LPWork\ErrorHandling\HttpDebugContext;
use LPWork\ErrorHandling\HttpExceptionHandler;
use LPWork\ErrorHandling\Renderers\HttpDebugExceptionRenderer;
use LPWork\Events\Providers\EventServiceProvider;
use LPWork\Kernels\Http\HttpKernel;
use LPWork\Logging\Contracts\Logger;
use LPWork\Logging\Enums\LogLevel;
use LPWork\Logging\LogChannel;
use LPWork\Middleware\SessionMiddleware;
use LPWork\Observability\DiagnosticsCollector;
use LPWork\Observability\DiagnosticsSnapshotFactory;
use LPWork\Observability\MetricCollector;
use LPWork\Observability\RequestDiagnosticsResetter;
use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;
use LPWork\Routing\Exceptions\InvalidRoutingConfigException;
use LPWork\Routing\Router;
use LPWork\Security\Csrf\CsrfConfig;
use LPWork\Security\Csrf\CsrfTokenManager;
use LPWork\Security\SecurityConfig;
use LPWork\Session\Contracts\SessionDriver;
use LPWork\Throttle\Storage\InMemoryThrottleStorage;
use LPWork\Throttle\ThrottleConfig;
use LPWork\Throttle\ThrottleLimiter;
use LPWork\Url\Url;
use LPWork\Validation\Providers\ValidationServiceProvider;
use Tests\support\ApplicationFactory;
use Tests\support\ApplicationTestEnvironment;
use Tests\support\http\HttpOutputBuffer;
use Tests\support\middleware\ContainerMiddleware;
use Tests\support\middleware\FirstMiddleware;
use Tests\support\middleware\InjectedHeader;
use Tests\support\middleware\NotMiddleware;
use Tests\support\middleware\SecondMiddleware;
use Tests\support\routing\ContainerController;
use Tests\support\routing\InjectedMessage;
use Tests\support\routing\TestController;
use Tests\support\security\SecurityConfigs;
use Tests\support\session\InMemorySessionDriver;
use Tests\support\testing\Http\CapturingHttpEmitter;
use Tests\support\testing\Http\HttpTestClient;
use Tests\support\testing\Logging\TestLogDriver;
use Tests\support\throttle\MutableThrottleClock;
use Tests\support\throttle\ThrottleConfigBuilder;

beforeEach(function (): void {
    Url::reset();
    Environment::reset();
    Config::reset();
});

afterEach(function (): void {
    Url::reset();
    Environment::reset();
    Config::reset();
});

afterAll(function (): void {
    ApplicationTestEnvironment::removeDirectories();
});

it('emits a matched route response for the current request', function (): void {
    $output = HttpOutputBuffer::create();
    $router = new Router();
    $router->get('/health', [TestController::class, 'index'])->name('health');

    $kernel = new HttpKernel(ApplicationFactory::create(), $output->emitter(), $router);

    $exitCode = $kernel->handle(HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/health?verbose=1',
    ]));

    expect($exitCode)->toBe(200)
        ->and($output->statusCode())->toBe(200)
        ->and($output->headers())->toBe(['Content-Type: text/plain; charset=UTF-8'])
        ->and($output->body())->toBe('GET /health');
});

it('injects an APP_DEBUG diagnostics bar before emitting html responses', function (): void {
    $app = ApplicationFactory::create();
    $context = new HttpDebugContext();
    $metrics = new MetricCollector();
    $diagnostics = new DiagnosticsCollector();
    $emitter = new CapturingHttpEmitter();
    $router = new Router();
    $router->get('/page', static fn(): HttpResponse => HttpResponse::html('<html><body>Page</body></html>'))->name('page');

    $app->container()->instance(HttpDebugContext::class, $context);
    $app->container()->instance(MetricCollector::class, $metrics);
    $app->container()->instance(DiagnosticsCollector::class, $diagnostics);
    $app->container()->instance(RequestDiagnosticsResetter::class, new RequestDiagnosticsResetter($diagnostics, $metrics));
    $app->container()->instance(
        DebugBarResponseInjector::class,
        new DebugBarResponseInjector(
            new DiagnosticsSnapshotFactory($context, $metrics, $diagnostics),
            new DebugBarRenderer(),
            new DebugBarRequestStore(sys_get_temp_dir() . '/lpwork-kernel-debugbar-test-' . bin2hex(random_bytes(4))),
            enabled: true,
        ),
    );

    $kernel = new HttpKernel($app, $emitter, $router);

    $kernel->handle(HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/page',
    ]));

    expect($emitter->response?->body())->toContain('id="lp-debug-bar"')
        ->and($emitter->response?->body())->toContain('http.request.duration')
        ->and($emitter->response?->body())->toContain('Page')
        ->and($emitter->response?->body())->toContain('</body>');
});

it('records application bootstrap metrics before request diagnostics are rendered', function (): void {
    $app = ApplicationFactory::create();
    $metrics = new MetricCollector();
    $diagnostics = new DiagnosticsCollector();
    $emitter = new CapturingHttpEmitter();
    $router = new Router();
    $router->get('/page', static fn(): HttpResponse => HttpResponse::html('<html><body>Page</body></html>'));

    $app->container()->instance(MetricCollector::class, $metrics);
    $app->container()->instance(DiagnosticsCollector::class, $diagnostics);
    $app->container()->instance(RequestDiagnosticsResetter::class, new RequestDiagnosticsResetter($diagnostics, $metrics));
    $app->container()->instance(
        HttpExceptionHandler::class,
        new HttpExceptionHandler(
            new class implements ExceptionReporter {
                public function report(Throwable $throwable): void {}
            },
            new class implements HttpExceptionRenderer {
                public function render(Throwable $throwable): HttpResponse
                {
                    return HttpResponse::html('Error', 500);
                }
            },
        ),
    );

    $kernel = new HttpKernel($app, $emitter, $router, applicationStartedAt: hrtime(true) - 5_000_000);
    $kernel->bootstrap();

    try {
        $kernel->handle(HttpRequest::fromArrays([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/page',
        ]));
    } finally {
        restore_error_handler();
    }

    expect(array_map(static fn($metric): string => $metric->name, $metrics->recent()))
        ->toContain('application.bootstrap')
        ->toContain('http.request.duration');
});

it('logs handled HTTP requests with route and duration context', function (): void {
    $driver = new TestLogDriver();
    $app = ApplicationFactory::create();
    $app->container()->instance(Logger::class, new LogChannel('app', $driver));
    $app->register(new EventServiceProvider());
    $router = new Router();
    $router->get('/health', [TestController::class, 'index'])->name('health');

    $kernel = new HttpKernel($app, new CapturingHttpEmitter(), $router);

    $kernel->handle(HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/health',
    ]));

    $record = $driver->records()[0];

    expect($record->level)->toBe(LogLevel::Info)
        ->and($record->message)->toBe('HTTP request handled.')
        ->and($record->context['method'])->toBe('GET')
        ->and($record->context['path'])->toBe('/health')
        ->and($record->context['status'])->toBe(200)
        ->and($record->context['route'])->toBe('health')
        ->and($record->context['duration_ms'])->toBeFloat();
});

it('logs formatted HTTP exceptions as failed requests', function (): void {
    $driver = new TestLogDriver();
    $app = ApplicationFactory::create();
    $app->container()->instance(Logger::class, new LogChannel('app', $driver));
    $app->register(new EventServiceProvider());
    $router = new Router();
    $router->get('/boom', static fn(): never => throw new RuntimeException('Boom'));

    $kernel = new HttpKernel($app, new CapturingHttpEmitter(), $router);

    $kernel->handle(HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/boom',
    ]));

    $record = $driver->records()[0];

    expect($record->level)->toBe(LogLevel::Error)
        ->and($record->message)->toBe('HTTP request failed.')
        ->and($record->context['status'])->toBe(500)
        ->and($record->context['exception'])->toBe(RuntimeException::class);
});

it('uses the application router from the container when no router is passed', function (): void {
    $output = HttpOutputBuffer::create();
    $app = ApplicationFactory::create();
    $router = new Router();
    $router->get('/container-route', [TestController::class, 'index']);
    $app->container()->instance(Router::class, $router);

    $kernel = new HttpKernel($app, $output->emitter());

    $kernel->handle(HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/container-route',
    ]));

    expect($output->body())->toBe('GET /container-route');
});

it('uses the Http emitter from the container when no emitter is passed', function (): void {
    $output = HttpOutputBuffer::create();
    $app = ApplicationFactory::create();
    $app->container()->instance(HttpEmitter::class, $output->emitter());

    $router = new Router();
    $router->get('/container-emitter', [TestController::class, 'index']);

    $kernel = new HttpKernel($app, router: $router);

    $kernel->handle(HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/container-emitter',
    ]));

    expect($output->body())->toBe('GET /container-emitter');
});

it('passes route parameters to controller actions', function (): void {
    $output = HttpOutputBuffer::create();
    $router = new Router();
    $router->get('/posts/{id}', [TestController::class, 'show'])->name('posts.show');

    $kernel = new HttpKernel(ApplicationFactory::create(), $output->emitter(), $router);

    $kernel->handle(HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/posts/15',
    ]));

    expect($output->body())->toBe('GET /posts/15 15');
});

it('resolves route controllers through the container', function (): void {
    $output = HttpOutputBuffer::create();
    $app = ApplicationFactory::create();
    $app->container()->instance(InjectedMessage::class, new InjectedMessage('injected-controller'));

    $router = new Router();
    $router->get('/container-controller', [ContainerController::class, 'index']);

    $kernel = new HttpKernel($app, $output->emitter(), $router);

    $kernel->handle(HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/container-controller',
    ]));

    expect($output->body())->toBe('GET /container-controller injected-controller');
});

it('parses JSON request bodies for API routes without requiring a session', function (): void {
    $output = HttpOutputBuffer::create();
    $router = new Router();
    $router->post('/api/articles', [TestController::class, 'apiStore'])->api();

    $kernel = new HttpKernel(ApplicationFactory::create(), $output->emitter(), $router);

    $exitCode = $kernel->handle(HttpRequest::fromArrays(
        server: [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/api/articles',
            'CONTENT_TYPE' => 'application/json; charset=UTF-8',
        ],
        body: '{"title":"API article"}',
    ));

    expect($exitCode)->toBe(201)
        ->and($output->statusCode())->toBe(201)
        ->and($output->headers())->toBe(['Content-Type: application/json; charset=UTF-8'])
        ->and($output->body())->toBe('{"title":"API article","session_attached":false}');
});

it('returns a JSON bad request response for malformed API JSON request bodies', function (): void {
    $output = HttpOutputBuffer::create();
    $router = new Router();
    $router->post('/api/articles', [TestController::class, 'apiStore'])->api();

    $kernel = new HttpKernel(ApplicationFactory::create(), $output->emitter(), $router);

    $exitCode = $kernel->handle(HttpRequest::fromArrays(
        server: [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/api/articles',
            'CONTENT_TYPE' => 'application/json',
        ],
        body: '{"title":',
    ));

    expect($exitCode)->toBe(400)
        ->and($output->statusCode())->toBe(400)
        ->and($output->headers())->toBe(['Content-Type: application/json; charset=UTF-8'])
        ->and($output->body())->toBe('{"error":{"status":400,"message":"Bad Request"}}');
});

it('does not parse JSON request bodies for web routes', function (): void {
    $output = HttpOutputBuffer::create();
    $router = new Router();
    $router->post('/articles', [TestController::class, 'inputTitle']);

    $kernel = new HttpKernel(ApplicationFactory::create(), $output->emitter(), $router);

    $exitCode = $kernel->handle(HttpRequest::fromArrays(
        server: [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/articles',
            'CONTENT_TYPE' => 'application/json',
        ],
        body: '{"title":"Web article"}',
    ));

    expect($exitCode)->toBe(200)
        ->and($output->headers())->toBe(['Content-Type: text/plain; charset=UTF-8'])
        ->and($output->body())->toBe('missing');
});

it('marks grouped routes as API routes', function (): void {
    $output = HttpOutputBuffer::create();
    $router = new Router();
    $router->group(['prefix' => 'api', 'api' => true], function (Router $router): void {
        $router->post('/articles', [TestController::class, 'apiStore']);
    });

    $kernel = new HttpKernel(ApplicationFactory::create(), $output->emitter(), $router);

    $kernel->handle(HttpRequest::fromArrays(
        server: [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/api/articles',
            'CONTENT_TYPE' => 'application/vnd.api+json',
        ],
        body: '{"title":"Grouped API article"}',
    ));

    expect($output->body())->toBe('{"title":"Grouped API article","session_attached":false}');
});

it('matches spoofed RESTful methods from post input', function (): void {
    $output = HttpOutputBuffer::create();
    $router = new Router();
    $router->put('/posts/{id}', [TestController::class, 'update'])->name('posts.update');

    $kernel = new HttpKernel(ApplicationFactory::create(), $output->emitter(), $router);

    $kernel->handle(HttpRequest::fromArrays(
        server: [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/posts/15',
        ],
        input: ['_method' => 'PUT'],
    ));

    expect($output->body())->toBe('POST /posts/15 15');
});

it('passes matched routes through their middleware pipeline', function (): void {
    $output = HttpOutputBuffer::create();
    $router = new Router();
    $router->group([
        'middleware' => FirstMiddleware::class,
    ], function (Router $router): void {
        $router->get('/profile', [TestController::class, 'index'])
            ->middleware(SecondMiddleware::class);
    });

    $kernel = new HttpKernel(ApplicationFactory::create(), $output->emitter(), $router);

    $kernel->handle(HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/profile',
    ]));

    expect($output->body())->toBe('GET /profile|second|first')
        ->and($output->headers())->toContain('X-First-Middleware: passed')
        ->and($output->headers())->toContain('X-Second-Middleware: passed')
        ->and($output->headers())->toContain('Content-Type: text/plain; charset=UTF-8');
});

it('passes matched routes through global middleware before route middleware', function (): void {
    $output = HttpOutputBuffer::create();
    $router = new Router();
    $router->globalMiddleware(FirstMiddleware::class);
    $router->get('/profile', [TestController::class, 'index'])
        ->middleware(SecondMiddleware::class);

    $kernel = new HttpKernel(ApplicationFactory::create(), $output->emitter(), $router);

    $kernel->handle(HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/profile',
    ]));

    expect($output->body())->toBe('GET /profile|second|first')
        ->and($output->headers())->toContain('X-First-Middleware: passed')
        ->and($output->headers())->toContain('X-Second-Middleware: passed');
});

it('applies globally configured route middleware through bootstrap', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->setEnvValue('APP_LANG', 'pl_PL');
    $environment->appendConfigValue('routing.php', 'middleware.aliases.first', FirstMiddleware::class);
    $environment->appendConfigValue('routing.php', 'middleware.global', ['first']);

    $app = Bootstrap::init($environment->basePath());
    $emitter = new CapturingHttpEmitter();

    new HttpKernel($app, $emitter)->handle(HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/',
    ]));

    expect($emitter->response?->body())->toEndWith('|first')
        ->and($emitter->response?->header('X-First-Middleware'))->toBe('passed');
});

it('resolves route middleware through the container', function (): void {
    $output = HttpOutputBuffer::create();
    $app = ApplicationFactory::create();
    $app->container()->instance(InjectedHeader::class, new InjectedHeader('injected-middleware'));

    $router = new Router();
    $router->get('/container-middleware', [TestController::class, 'index'])
        ->middleware(ContainerMiddleware::class);

    $kernel = new HttpKernel($app, $output->emitter(), $router);

    $kernel->handle(HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/container-middleware',
    ]));

    expect($output->body())->toBe('GET /container-middleware|container-middleware')
        ->and($output->headers())->toContain('X-Container-Middleware: injected-middleware');
});

it('rejects invalid route middleware before the Http kernel runs', function (): void {
    $router = new Router();

    expect(fn() => $router->get('/invalid-middleware', [TestController::class, 'index'])
        ->middleware(NotMiddleware::class))
        ->toThrow(InvalidRoutingConfigException::class, 'Route middleware must implement the middleware contract');
});

it('renders LPWork framework context for exceptions through the Http kernel', function (): void {
    $app = ApplicationFactory::create();
    $context = new HttpDebugContext();
    $context->addProvider(new HttpRequestDebugContextProvider());
    $context->addProvider(new RouteDebugContextProvider());
    $context->addProvider(new MiddlewareDebugContextProvider());
    $context->addProvider(new SessionDebugContextProvider());
    $metrics = new MetricCollector();
    $diagnostics = new DiagnosticsCollector();
    $snapshots = new DiagnosticsSnapshotFactory($context, $metrics, $diagnostics);
    $app->container()->instance(HttpDebugContext::class, $context);
    $app->container()->instance(MetricCollector::class, $metrics);
    $app->container()->instance(HttpExceptionRenderer::class, new HttpDebugExceptionRenderer($app->basePath(), $context, $snapshots));

    $router = new Router();
    $router->get('/debug/{id}', static function (): HttpResponse {
        throw new RuntimeException('Kernel debug failure');
    })
        ->middleware(FirstMiddleware::class)
        ->name('debug.show');

    $emitter = new CapturingHttpEmitter();
    $kernel = new HttpKernel($app, $emitter, $router);

    expect($kernel->handle(HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/debug/15?tab=trace',
        'HTTP_HOST' => 'example.test',
    ])))->toBe(500)
        ->and($emitter->response?->body())->toContain('Kernel debug failure')
        ->and($emitter->response?->body())->toContain('http://example.test/debug/15?tab=trace')
        ->and($emitter->response?->body())->toContain('debug.show')
        ->and($emitter->response?->body())->toContain('id')
        ->and($emitter->response?->body())->toContain('15')
        ->and($emitter->response?->body())->toContain(FirstMiddleware::class)
        ->and($emitter->response?->body())->toContain('http.request.duration')
        ->and($emitter->response?->body())->toContain('Kernel debug failure');
});

it('rejects invalid JSON route middleware declarations before the Http kernel runs', function (): void {
    $router = new Router();

    expect(fn() => $router->get('/invalid-middleware', [TestController::class, 'index'])
        ->middleware(NotMiddleware::class))
        ->toThrow(InvalidRoutingConfigException::class, 'Route middleware must implement the middleware contract');
});

it('renders validation exceptions as JSON 422 responses', function (): void {
    $app = ApplicationFactory::create();
    $app->register(new ValidationServiceProvider());

    $router = new Router();
    $router->post('/posts', [TestController::class, 'invalidFormRequestShouldNotRun'])->api();

    HttpTestClient::forApplication($app, $router)
        ->postJson('/posts', ['title' => ''])
        ->assertStatus(422)
        ->assertHeader('Content-Type', 'application/json; charset=UTF-8')
        ->assertJsonPath('error.status', 422)
        ->assertJsonPath('error.message', 'Unprocessable Entity')
        ->assertJsonPath('errors.title.0.message.key', 'validation.required');
});

it('renders web validation exceptions as JSON when JSON is the preferred accepted format', function (): void {
    $app = ApplicationFactory::create();
    $app->register(new ValidationServiceProvider());

    $router = new Router();
    $router->post('/posts', [TestController::class, 'invalidFormRequestShouldNotRun']);

    HttpTestClient::forApplication($app, $router)
        ->post('/posts', ['title' => ''], headers: [
            'Accept' => 'application/json;q=0.9, text/html;q=0.1',
        ])
        ->assertStatus(422)
        ->assertHeader('Content-Type', 'application/json; charset=UTF-8')
        ->assertJsonPath('error.status', 422)
        ->assertJsonPath('errors.title.0.message.key', 'validation.required');
});

it('keeps web validation exceptions as redirects when HTML is the preferred accepted format', function (): void {
    $app = ApplicationFactory::create();
    $app->register(new ValidationServiceProvider());

    $router = new Router();
    $router->post('/posts', [TestController::class, 'invalidFormRequestShouldNotRun']);

    HttpTestClient::forApplication($app, $router)
        ->withSession()
        ->post('/posts', ['title' => ''], headers: [
            'Accept' => 'application/json;q=0.2, text/html;q=0.9',
            'Referer' => '/posts/create',
        ])
        ->assertRedirect('/posts/create')
        ->assertSessionError('title.0.message.key', 'validation.required');
});

it('renders API validation exceptions as JSON even when HTML is accepted first', function (): void {
    $app = ApplicationFactory::create();
    $app->register(new ValidationServiceProvider());

    $router = new Router();
    $router->post('/posts', [TestController::class, 'invalidFormRequestShouldNotRun'])->api();

    HttpTestClient::forApplication($app, $router)
        ->postJson('/posts', ['title' => ''], headers: [
            'Accept' => 'text/html',
        ])
        ->assertStatus(422)
        ->assertHeader('Content-Type', 'application/json; charset=UTF-8')
        ->assertJsonPath('error.status', 422)
        ->assertJsonPath('errors.title.0.message.key', 'validation.required');
});

it('redirects back with flashed validation errors and old input for HTML validation exceptions', function (): void {
    $app = ApplicationFactory::create();
    $app->register(new ValidationServiceProvider());

    $router = new Router();
    $router->post('/posts', [TestController::class, 'invalidFormRequestShouldNotRun']);

    $client = HttpTestClient::forApplication($app, $router)
        ->withSession();

    $client->post('/posts', [
        'title' => '',
        'meta' => ['published' => 'maybe'],
    ], headers: [
        'Referer' => '/posts/create',
    ])
        ->assertRedirect('/posts/create')
        ->assertOldInput('title', '')
        ->assertOldInput('meta.published', 'maybe')
        ->assertSessionError('title.0.message.key', 'validation.required')
        ->assertSessionError('meta.published.0.message.key', 'validation.boolean');

    $client->session()
        ->assertSaved(1)
        ->assertOldInput('title', '')
        ->assertError('title.0.message.key', 'validation.required');
});

it('negotiates exception responses from accepted response format preferences', function (): void {
    $router = new Router();
    $router->get('/invalid', [TestController::class, 'invalidResponse']);

    $json = HttpTestClient::forApplication(ApplicationFactory::create(), $router)
        ->get('/invalid', headers: [
            'Accept' => 'application/json;q=0.9, text/html;q=0.1',
        ]);

    $json->assertStatus(500)
        ->assertHeader('Content-Type', 'application/json; charset=UTF-8')
        ->assertJsonPath('error.status', 500);

    $html = HttpTestClient::forApplication(ApplicationFactory::create(), $router)
        ->get('/invalid', headers: [
            'Accept' => 'application/json;q=0.2, text/html;q=0.9',
        ]);

    $html->assertStatus(500)
        ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
        ->assertSee('Server error');
});

it('emits an Http exception response when the Http flow throws', function (): void {
    $emitter = new CapturingHttpEmitter(failFirstEmit: true);
    $router = new Router();
    $router->get('/health', [TestController::class, 'index']);

    $kernel = new HttpKernel(ApplicationFactory::create(), $emitter, $router);

    expect($kernel->handle(HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/health',
    ])))->toBe(500)
        ->and($emitter->calls)->toBe(2)
        ->and($emitter->response)->toBeInstanceOf(HttpResponse::class)
        ->and($emitter->response?->statusCode())->toBe(500)
        ->and($emitter->response?->body())->toContain('Server error')
        ->and($emitter->response?->body())->toContain('/assets/lpwork-logo.svg?v=')
        ->and($emitter->response?->body())->toContain('class="lp-ui-status-code">500</p>');
});

it('emits an Http exception response when route action returns invalid response', function (): void {
    $router = new Router();
    $router->get('/invalid', [TestController::class, 'invalidResponse']);

    $emitter = new CapturingHttpEmitter();
    $kernel = new HttpKernel(ApplicationFactory::create(), $emitter, $router);

    expect($kernel->handle(HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/invalid',
    ])))->toBe(500)
        ->and($emitter->response?->statusCode())->toBe(500);
});

it('runs configured HTTP security before route dispatch and adds security headers', function (): void {
    $app = ApplicationFactory::create();
    $app->container()->instance(SecurityConfig::class, SecurityConfigs::http(
        enforceHttps: true,
        sendSecurityHeaders: true,
        trustedHosts: ['example.com'],
        headers: ['X-Content-Type-Options' => 'nosniff'],
    ));

    $router = new Router();
    $router->get('/secure', [TestController::class, 'index']);

    $emitter = new CapturingHttpEmitter();
    $kernel = new HttpKernel($app, $emitter, $router);

    $kernel->handle(HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/secure',
        'HTTP_HOST' => 'example.com',
        'HTTPS' => 'on',
    ]));

    expect($emitter->response?->statusCode())->toBe(200)
        ->and($emitter->response?->header('X-Content-Type-Options'))->toBe('nosniff')
        ->and($emitter->response?->header('Strict-Transport-Security'))->toBe('max-age=31536000; includeSubDomains');

    $kernel->handle(HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/secure',
        'HTTP_HOST' => 'evil.test',
        'HTTPS' => 'on',
    ]));

    expect($emitter->response?->statusCode())->toBe(400);
});

it('applies CSRF only to configured web session routes', function (): void {
    $app = ApplicationFactory::create();
    $sessionDriver = new InMemorySessionDriver();
    $security = SecurityConfigs::http(csrfEnabled: true);
    $app->container()->instance(SecurityConfig::class, $security);
    $app->container()->instance(CsrfConfig::class, $security->csrf());
    $app->container()->instance(CsrfTokenManager::class, new CsrfTokenManager($security->csrf()));
    $app->container()->instance(SessionDriver::class, $sessionDriver);
    $app->container()->instance(SessionMiddleware::class, new SessionMiddleware($sessionDriver));

    $router = new Router();
    $router->get('/form', [TestController::class, 'index']);
    $router->post('/form', [TestController::class, 'inputTitle']);
    $router->post('/api/form', [TestController::class, 'apiStore'])->api();

    $emitter = new CapturingHttpEmitter();
    $kernel = new HttpKernel($app, $emitter, $router);

    $kernel->handle(HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/form',
    ]));

    $token = $sessionDriver->data()['_csrf_token'] ?? null;

    expect($token)->toBeString()
        ->and($sessionDriver->starts)->toBe(1);

    $kernel->handle(HttpRequest::fromArrays(
        server: [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/form',
        ],
        input: [
            'title' => 'Protected form',
            '_token' => $token,
        ],
    ));

    expect($emitter->response?->statusCode())->toBe(200)
        ->and($emitter->response?->body())->toBe('Protected form');

    $kernel->handle(HttpRequest::fromArrays(
        server: [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/form',
        ],
        input: ['title' => 'Missing token'],
    ));

    expect($emitter->response?->statusCode())->toBe(403);

    $kernel->handle(HttpRequest::fromArrays(
        server: [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/api/form',
            'CONTENT_TYPE' => 'application/json',
        ],
        body: '{"title":"API without CSRF"}',
    ));

    expect($emitter->response?->statusCode())->toBe(201)
        ->and($emitter->response?->body())->toBe('{"title":"API without CSRF","session_attached":false}');
});

it('uses env-backed CSRF config for web routes without applying it to API routes', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->setEnvValue('SECURITY_CSRF_ENABLED', true);

    $app = Bootstrap::init($environment->basePath());
    $sessionDriver = new InMemorySessionDriver();
    $app->container()->instance(SessionDriver::class, $sessionDriver);
    $app->container()->instance(SessionMiddleware::class, new SessionMiddleware($sessionDriver));

    $router = new Router();
    $router->get('/form', [TestController::class, 'index']);
    $router->post('/form', [TestController::class, 'inputTitle']);
    $router->post('/api/form', [TestController::class, 'apiStore'])->api();

    $emitter = new CapturingHttpEmitter();
    $kernel = new HttpKernel($app, $emitter, $router);

    $kernel->handle(HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/form',
    ]));

    $token = $sessionDriver->data()['_csrf_token'] ?? null;

    expect($token)->toBeString()
        ->and($sessionDriver->starts)->toBe(1);

    $kernel->handle(HttpRequest::fromArrays(
        server: [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/form',
        ],
        input: [
            'title' => 'Env protected form',
            '_token' => $token,
        ],
    ));

    expect($emitter->response?->statusCode())->toBe(200)
        ->and($emitter->response?->body())->toBe('Env protected form')
        ->and($sessionDriver->starts)->toBe(2);

    $kernel->handle(HttpRequest::fromArrays(
        server: [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/api/form',
            'CONTENT_TYPE' => 'application/json',
        ],
        body: '{"title":"Env API without CSRF"}',
    ));

    expect($emitter->response?->statusCode())->toBe(201)
        ->and($emitter->response?->body())->toBe('{"title":"Env API without CSRF","session_attached":false}')
        ->and($sessionDriver->starts)->toBe(2);
});

it('skips web CSRF middleware when env disables it', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->setEnvValue('SECURITY_CSRF_ENABLED', false);

    $app = Bootstrap::init($environment->basePath());
    $sessionDriver = new InMemorySessionDriver();
    $app->container()->instance(SessionDriver::class, $sessionDriver);
    $app->container()->instance(SessionMiddleware::class, new SessionMiddleware($sessionDriver));

    $router = new Router();
    $router->post('/form', [TestController::class, 'inputTitle']);

    $emitter = new CapturingHttpEmitter();
    $kernel = new HttpKernel($app, $emitter, $router);

    $kernel->handle(HttpRequest::fromArrays(
        server: [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/form',
        ],
        input: ['title' => 'Unprotected by env'],
    ));

    expect($emitter->response?->statusCode())->toBe(200)
        ->and($emitter->response?->body())->toBe('Unprotected by env')
        ->and($sessionDriver->starts)->toBe(0);
});

it('applies separate throttle policies to web and API routes', function (): void {
    $app = ApplicationFactory::create();
    $app->container()->instance(ThrottleConfig::class, ThrottleConfigBuilder::config(web: true, api: true, maxAttempts: 1));
    $app->container()->instance(ThrottleLimiter::class, new ThrottleLimiter(
        new InMemoryThrottleStorage(),
        new MutableThrottleClock(),
    ));

    $router = new Router();
    $router->get('/web-throttle', [TestController::class, 'index']);
    $router->get('/api/throttle', [TestController::class, 'index'])->api();

    $emitter = new CapturingHttpEmitter();
    $kernel = new HttpKernel($app, $emitter, $router);

    $kernel->handle(HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/web-throttle',
        'REMOTE_ADDR' => '127.0.0.1',
    ]));

    expect($emitter->response?->statusCode())->toBe(200)
        ->and($emitter->response?->header('X-RateLimit-Remaining'))->toBe('0');

    $kernel->handle(HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/web-throttle',
        'REMOTE_ADDR' => '127.0.0.1',
    ]));

    expect($emitter->response?->statusCode())->toBe(429)
        ->and($emitter->response?->header('Retry-After'))->toBe('60');

    $kernel->handle(HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/api/throttle',
        'REMOTE_ADDR' => '127.0.0.1',
        'HTTP_ACCEPT' => 'application/json',
    ]));

    expect($emitter->response?->statusCode())->toBe(200)
        ->and($emitter->response?->header('X-RateLimit-Remaining'))->toBe('0');
});
