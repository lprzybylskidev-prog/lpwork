<?php

declare(strict_types=1);

use LPWork\DebugBar\DebugBarPageRenderer;
use LPWork\DebugBar\DebugBarRenderer;
use LPWork\DebugBar\DebugBarRequestStore;
use LPWork\ErrorHandling\Context\HttpRequestDebugContextProvider;
use LPWork\ErrorHandling\Context\MiddlewareDebugContextProvider;
use LPWork\ErrorHandling\Context\RouteDebugContextProvider;
use LPWork\ErrorHandling\Context\SessionDebugContextProvider;
use LPWork\ErrorHandling\Contracts\HttpDebugContextProvider;
use LPWork\ErrorHandling\HttpDebugContext;
use LPWork\ErrorHandling\Renderers\HttpDebugExceptionRenderer;
use LPWork\Foundation\FrameworkMetadata;
use LPWork\Http\Exceptions\NotFoundException;
use LPWork\Logging\Enums\LogLevel;
use LPWork\Middleware\Contracts\Middleware;
use LPWork\Observability\DiagnosticsCollector;
use LPWork\Observability\DiagnosticsSnapshotFactory;
use LPWork\Observability\Metric;
use LPWork\Observability\MetricCollector;
use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;
use LPWork\Routing\Route;
use LPWork\Routing\RouteAction;
use LPWork\Routing\RouteMatch;
use LPWork\Session\Session;

it('renders debug exceptions as Http responses', function (): void {
    $response = new HttpDebugExceptionRenderer(\Tests\support\ProjectPaths::root())->render(new RuntimeException('Test exception'));

    expect($response->statusCode())->toBe(500)
        ->and($response->headers())->toBe(['Content-Type' => 'text/html; charset=UTF-8'])
        ->and($response->body())->toContain('Test exception')
        ->and($response->body())->toContain('/assets/lpwork-logo.svg?v=')
        ->and($response->body())->toContain('--lp-ui-bg: #090b0f')
        ->and($response->body())->toContain('/favicon.svg?v=')
        ->and($response->body())->toContain('Trace and source')
        ->and($response->body())->toContain('Framework context')
        ->and($response->body())->toContain('border-right: 2px solid var(--lp-ui-blue-strong)')
        ->and($response->body())->not->toContain('clip-path: polygon(50% 72%, 16% 38%, 28% 26%, 50% 48%, 72% 26%, 84% 38%)')
        ->and($response->body())->not->toContain('Caused by')
        ->and($response->body())->toContain('All')
        ->and($response->body())->toContain('App')
        ->and($response->body())->toContain('LPWork')
        ->and($response->body())->toContain('LPWork ' . FrameworkMetadata::VERSION)
        ->and($response->body())->not->toContain(implode('', ['Search', ' for help']))
        ->and($response->body())->not->toContain(implode('', ['google.com', '/search']))
        ->and($response->body())->not->toContain(implode('', ['stackoverflow.com', '/search']))
        ->and($response->body())->not->toContain(implode('', ['GET', ' Data']))
        ->and($response->body())->not->toContain(implode('', ['Server', '/Request Data']))
        ->and($response->body())->not->toContain(implode('', ['Registered', ' Handlers']));
});

it('renders debug Http exceptions with their status code', function (): void {
    $response = new HttpDebugExceptionRenderer(\Tests\support\ProjectPaths::root())->render(new NotFoundException('Missing page'));

    expect($response->statusCode())->toBe(404)
        ->and($response->headers())->toBe(['Content-Type' => 'text/html; charset=UTF-8'])
        ->and($response->body())->toContain('Missing page');
});

it('renders previous exceptions without merging them into the main trace', function (): void {
    try {
        try {
            throw new RuntimeException('a');
        } catch (Throwable $exception) {
            throw new Exception('b', previous: $exception);
        }
    } catch (Throwable $exception) {
        $response = new HttpDebugExceptionRenderer(\Tests\support\ProjectPaths::root())->render(new Exception('c', previous: $exception));
    }

    expect($response->body())->toContain('Previous exceptions')
        ->and($response->body())->toContain('data-lp-panel-tab="previous"')
        ->and($response->body())->toContain('Exception: b')
        ->and($response->body())->toContain('RuntimeException: a')
        ->and($response->body())->toContain('previous #1')
        ->and($response->body())->toContain('previous #2');
});

it('renders LPWork request session route and middleware context', function (): void {
    $context = defaultDebugContext();
    $route = new Route(['GET'], '/users/{id}', RouteAction::fromArray([DebugController::class, 'show']));
    $route->setName('users.show');
    $route->middleware(['web', DebugMiddleware::class]);
    $context->setRouteMatch(new RouteMatch($route, ['id' => '15']));
    $context->setMiddleware([new DebugMiddleware()]);
    $context->setRequest(
        HttpRequest::fromArrays(
            server: [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/users/15?tab=profile',
                'HTTP_HOST' => 'example.test',
                'HTTP_USER_AGENT' => 'LPWork Browser',
            ],
            query: ['tab' => 'profile'],
            input: ['name' => 'Ada'],
            cookies: ['theme' => 'dark'],
        )->withSession(new Session(['user_id' => 15])),
    );

    $response = new HttpDebugExceptionRenderer(\Tests\support\ProjectPaths::root(), $context)->render(new RuntimeException('Context failure'));
    $contextStart = strpos($response->body(), 'Framework context');
    $contextHtml = $contextStart === false ? '' : substr($response->body(), $contextStart);

    expect($response->body())->toContain('Request')
        ->and($response->body())->toContain('http://example.test/users/15?tab=profile')
        ->and($response->body())->toContain('LPWork Browser')
        ->and($contextHtml)->toContain('lp-debug-fields')
        ->and($contextHtml)->toContain('lp-debug-tree')
        ->and($contextHtml)->toContain('lp-debug-tree-toggle')
        ->and($contextHtml)->toContain('lp-debug-token')
        ->and($contextHtml)->toContain('is-true')
        ->and($contextHtml)->not->toContain(implode('', ['array', ':']))
        ->and($response->body())->toContain('Route')
        ->and($response->body())->toContain('users.show')
        ->and($response->body())->toContain('DebugController@show')
        ->and($response->body())->toContain('Middleware')
        ->and($response->body())->toContain(DebugMiddleware::class)
        ->and($response->body())->toContain('Session')
        ->and($response->body())->toContain('user_id')
        ->and($response->body())->toContain('15');
});

it('allows applications to extend debug context', function (): void {
    $context = defaultDebugContext();
    $context->addProvider(new class implements HttpDebugContextProvider {
        public function context(HttpDebugContext $context): array
        {
            return [
                'Feature flags' => [
                    'checkout' => true,
                ],
            ];
        }
    });

    $response = new HttpDebugExceptionRenderer(\Tests\support\ProjectPaths::root(), $context)->render(new RuntimeException('Custom context failure'));

    expect($response->body())->toContain('Feature flags')
        ->and($response->body())->toContain('checkout')
        ->and($response->body())->toContain('true');
});

it('renders controller frames invoked through reflection with source code', function (): void {
    try {
        new ReflectionMethod(new ReflectionTraceDebugController(), 'fail')->invokeArgs(new ReflectionTraceDebugController(), []);
    } catch (RuntimeException $exception) {
        $response = new HttpDebugExceptionRenderer(\Tests\support\ProjectPaths::root())->render($exception);

        expect($response->body())->toContain('ReflectionTraceDebugController')
            ->and($response->body())->toContain(__FILE__)
            ->and($response->body())->toContain('lp-src-keyword')
            ->and($response->body())->toContain('lp-src-name')
            ->and($response->body())->toContain('fail')
            ->and($response->body())->not->toContain('[internal]</strong>');

        return;
    }

    throw new RuntimeException('Expected reflection-invoked controller action to throw.');
});

it('renders diagnostics captured for the failing request', function (): void {
    $context = defaultDebugContext();
    $diagnostics = new DiagnosticsCollector();
    $metrics = new MetricCollector();
    $snapshots = new DiagnosticsSnapshotFactory($context, $metrics, $diagnostics);

    $metrics->report(new Metric('http.request.duration', 12.4, 'ms', ['status' => 500], 10.1));
    $diagnostics->recordLog('app', LogLevel::Error, 'Checkout failed', ['order_id' => 42]);

    $response = new HttpDebugExceptionRenderer(\Tests\support\ProjectPaths::root(), $context, $snapshots)
        ->render(new RuntimeException('Diagnostics failure'));

    expect($response->body())->toContain('Diagnostics')
        ->and($response->body())->toContain('Metrics')
        ->and($response->body())->toContain('http.request.duration')
        ->and($response->body())->toContain('Logs')
        ->and($response->body())->toContain('Checkout failed')
        ->and($response->body())->toContain('order_id');
});

it('renders a collapsed debugbar launcher on debug exception pages when diagnostics are available', function (): void {
    $context = defaultDebugContext();
    $diagnostics = new DiagnosticsCollector();
    $metrics = new MetricCollector();
    $snapshots = new DiagnosticsSnapshotFactory($context, $metrics, $diagnostics);
    $debugBar = new DebugBarPageRenderer(
        $snapshots,
        new DebugBarRenderer(),
        new DebugBarRequestStore(sys_get_temp_dir() . '/lpwork-exception-debugbar-test-' . bin2hex(random_bytes(4))),
        enabled: true,
    );

    $metrics->report(new Metric('http.request.duration', 12.4, 'ms', ['status' => 500], 10.1));

    $response = new HttpDebugExceptionRenderer(\Tests\support\ProjectPaths::root(), $context, $snapshots, debugBar: $debugBar)
        ->render(new RuntimeException('Diagnostics failure'));

    expect($response->body())->toContain('Diagnostics failure')
        ->and($response->body())->toContain('data-lp-debug-collapsed="1"')
        ->and($response->body())->toContain('<button type="button" class="lp-debug-dock"')
        ->and($response->body())->toContain('http.request.duration');
});

it('renders empty repeatable diagnostics consistently on the debug exception page', function (): void {
    $context = defaultDebugContext();
    $context->addProvider(new class implements HttpDebugContextProvider {
        /**
         * @return array<string, mixed>
         */
        public function context(HttpDebugContext $context): array
        {
            return [
                'Events' => [],
                'Database' => [
                    'Queries' => [],
                ],
            ];
        }
    });

    $snapshots = new DiagnosticsSnapshotFactory($context, new MetricCollector(), new DiagnosticsCollector());

    $response = new HttpDebugExceptionRenderer(\Tests\support\ProjectPaths::root(), $context, $snapshots)
        ->render(new RuntimeException('Empty diagnostics failure'));

    expect($response->body())->toContain('No events recorded.')
        ->and($response->body())->toContain('No database queries recorded.')
        ->and($response->body())->not->toContain('<div class="lp-debug-field-name">Queries</div>');
});

it('renders a compact exception summary for clipboard sharing', function (): void {
    $context = defaultDebugContext();
    $context->setRequest(
        HttpRequest::fromArrays(
            server: [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/checkout',
                'HTTP_HOST' => 'example.test',
            ],
        ),
    );

    $diagnostics = new DiagnosticsCollector();
    $metrics = new MetricCollector();
    $snapshots = new DiagnosticsSnapshotFactory($context, $metrics, $diagnostics);

    $metrics->report(new Metric('http.request.duration', 42.5, 'ms', ['status' => 500], 41.9));
    $diagnostics->recordLog('app', LogLevel::Error, 'Payment failed', ['order_id' => 42]);

    $response = new HttpDebugExceptionRenderer(\Tests\support\ProjectPaths::root(), $context, $snapshots)
        ->render(new RuntimeException('Clipboard failure'));

    preg_match('/data-lp-copy-exception="([^"]+)"/', $response->body(), $matches);
    $summary = html_entity_decode($matches[1] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    expect($response->body())->toContain('data-lp-copy-exception=')
        ->and($response->body())->toContain('data-lp-copy-label="Copy"')
        ->and($summary)->toContain('# LPWork Debug Exception')
        ->and($summary)->toContain('Class: RuntimeException')
        ->and($summary)->toContain('Message: Clipboard failure')
        ->and($summary)->toContain('Framework: LPWork ' . FrameworkMetadata::VERSION)
        ->and($summary)->toContain('URL: http://example.test/checkout')
        ->and($summary)->toContain('## Top Frames')
        ->and($summary)->toContain('## Framework Context')
        ->and($summary)->toContain('http.request.duration=42.5 ms')
        ->and($summary)->toContain('Payment failed')
        ->and($summary)->not->toContain('lp-debug-source-line');
});

function defaultDebugContext(): HttpDebugContext
{
    $context = new HttpDebugContext();
    $context->addProvider(new HttpRequestDebugContextProvider());
    $context->addProvider(new RouteDebugContextProvider());
    $context->addProvider(new MiddlewareDebugContextProvider());
    $context->addProvider(new SessionDebugContextProvider());

    return $context;
}

final class DebugController
{
    public function show(): void {}
}

final class ReflectionTraceDebugController
{
    public function fail(): never
    {
        throw new RuntimeException('Reflection trace failure.');
    }
}

final readonly class DebugMiddleware implements Middleware
{
    public function handle(HttpRequest $request, Closure $next): HttpResponse
    {
        return $next($request);
    }
}
