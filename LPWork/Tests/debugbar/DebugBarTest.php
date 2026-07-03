<?php

declare(strict_types=1);

use LPWork\DebugBar\DebugBarRenderer;
use LPWork\DebugBar\DebugBarRequestStore;
use LPWork\DebugBar\DebugBarResponseInjector;
use LPWork\DebugBar\Providers\DebugBarServiceProvider;
use LPWork\ErrorHandling\HttpDebugContext;
use LPWork\Foundation\Application;
use LPWork\Foundation\FrameworkMetadata;
use LPWork\Foundation\Providers\FoundationServiceProvider;
use LPWork\Observability\DiagnosticsCollector;
use LPWork\Observability\DiagnosticsSnapshot;
use LPWork\Observability\DiagnosticsSnapshotFactory;
use LPWork\Observability\Metric;
use LPWork\Observability\MetricCollector;
use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;
use Tests\support\ApplicationTestEnvironment;

afterAll(function (): void {
    ApplicationTestEnvironment::removeDirectories();
});

it('renders a collapsible LPWork debug bar from a diagnostics snapshot', function (): void {
    $html = new DebugBarRenderer()->render(new DiagnosticsSnapshot(
        groups: [
            'Request' => [
                'Method' => 'GET',
                'URL' => 'http://example.test',
            ],
            'Database' => [
                'Queries' => [
                    ['SQL' => 'select 1', 'Duration ms' => 1.2],
                ],
            ],
        ],
        metrics: [
            new Metric('http.request.duration', 3.4, 'ms', ['status' => 200], 10.0, 1024 * 1024),
            new Metric('events.dispatched', 0.8, 'ms', [], 12.0, 2 * 1024 * 1024),
        ],
        logs: [
            ['channel' => 'app', 'level' => 'info', 'message' => 'Ready', 'context' => ['scope' => ['module' => 'welcome']]],
        ],
    ));

    expect($html)->toContain('id="lp-debug-bar"')
        ->and($html)->toContain('data-lp-debug-collapsed="0"')
        ->and($html)->toContain('data-lp-debug-toggle')
        ->and($html)->toContain('localStorage')
        ->and($html)->toContain('LPWork')
        ->and($html)->toContain('/assets/lpwork-logo.svg?v=')
        ->and($html)->toContain('height:28px;width:28px')
        ->and($html)->toContain('--lp-ui-bg: #090b0f')
        ->and($html)->toContain('class="lp-debug-console"')
        ->and($html)->toContain('.lp-debug-bar .lp-debug-workbench')
        ->and($html)->toContain('.lp-debug-bar .lp-debug-record>summary:after')
        ->and($html)->toContain('border-right:2px solid var(--lp-blue)')
        ->and($html)->not->toContain('clip-path:polygon(50% 72%,16% 38%,28% 26%,50% 48%,72% 26%,84% 38%)')
        ->and($html)->toContain('.lp-debug-bar .lp-debug-panels{height:100%;overflow:auto;padding:12px}')
        ->and($html)->toContain('GET http://example.test')
        ->and($html)->toContain('LPWork ' . FrameworkMetadata::VERSION)
        ->and($html)->toContain('PHP ')
        ->and($html)->toContain('1 queries / 0 events / 0 views / 0 cache / 0 queue')
        ->and($html)->toContain('data-lp-debug-tab="metrics"')
        ->and($html)->toContain('data-lp-debug-tab="websocket"><span>WebSocket</span><b>0</b>')
        ->and($html)->toContain('No websocket activity recorded.')
        ->and($html)->toContain('<b>2</b>')
        ->and($html)->toContain('lp-debug-timeline')
        ->and($html)->toContain('lp-debug-timeline-head')
        ->and($html)->toContain('lp-debug-timeline-row')
        ->and($html)->toContain('lp-debug-timeline-bar')
        ->and($html)->toContain('lp-debug-timeline-event')
        ->and($html)->toContain('lp-debug-timeline-cell')
        ->and($html)->toContain('style="left:')
        ->and($html)->toContain('2 MB')
        ->and($html)->toContain('0.8 ms')
        ->and($html)->toContain('lp-debug-tree')
        ->and($html)->toContain('lp-debug-tree-toggle')
        ->and($html)->toContain('<details class="lp-debug-tree-node is-branch"><summary>')
        ->and($html)->toContain('lp-debug-field-group')
        ->and($html)->toContain('lp-debug-inline-record')
        ->and($html)->not->toContain('lp-debug-info-pill')
        ->and($html)->not->toContain('lp-debug-context-table')
        ->and($html)->not->toContain('lp-debug-context-nested')
        ->and($html)->not->toContain('cursor:help')
        ->and($html)->not->toContain('lp-debug-metric-record')
        ->and($html)->not->toContain('<summary class="lp-debug-timeline-row">')
        ->and($html)->not->toContain('data-lp-debug-open-record="metric-0"')
        ->and($html)->toContain('<details class="lp-debug-record">')
        ->and($html)->toContain('<button type="button" class="lp-debug-dock"')
        ->and($html)->toContain('http.request.duration');
});

it('can render a debug bar payload as an initially collapsed launcher', function (): void {
    $renderer = new DebugBarRenderer();
    $payload = $renderer->payload(new DiagnosticsSnapshot(
        groups: [
            'Request' => ['Method' => 'GET', 'Path' => '/debug'],
        ],
        metrics: [],
        logs: [],
    ), 'request-one', 'session-one');

    $html = $renderer->renderPayload($payload, collapsed: true);

    expect($html)->toContain('class="lp-debug-bar is-collapsed"')
        ->and($html)->toContain('data-lp-debug-collapsed="1"')
        ->and($html)->toContain('bar.getAttribute("data-lp-debug-collapsed")==="1"')
        ->and($html)->toContain('<button type="button" class="lp-debug-dock"');
});

it('positions metric timeline bars from their measured starts and durations', function (): void {
    $html = new DebugBarRenderer()->render(new DiagnosticsSnapshot(
        groups: [],
        metrics: [
            new Metric('scheduler.task.demo', 10.0, 'ms', [], 105.0, 1024 * 1024),
            new Metric('http.request.duration', 20.0, 'ms', ['status' => 200], 100.0, 1024 * 1024),
            new Metric('application.bootstrap', 5.0, 'ms', [], 100.0, 1024 * 1024),
        ],
        logs: [],
    ));

    expect($html)->toContain('application.bootstrap')
        ->and($html)->toContain('scheduler.task.demo')
        ->and($html)->toContain('http.request.duration')
        ->and($html)->toContain('style="left:0%;width:25%"')
        ->and($html)->toContain('style="left:25%;width:50%"')
        ->and($html)->toContain('style="left:0%;width:100%"')
        ->and($html)->toContain('<span class="lp-debug-timeline-cell">+5ms</span>')
        ->and($html)->toContain('<span class="lp-debug-timeline-cell">+0ms</span>');

    expect($html)->toMatch('/application\.bootstrap.*http\.request\.duration.*scheduler\.task\.demo/s');
});

it('exposes short metric bar durations through a hover tooltip', function (): void {
    $html = new DebugBarRenderer()->render(new DiagnosticsSnapshot(
        groups: [],
        metrics: [
            new Metric('http.request.duration', 100.0, 'ms', ['status' => 200], 0.0, 1024 * 1024),
            new Metric('tiny.middleware', 1.25, 'ms', [], 50.0, 1024 * 1024),
        ],
        logs: [],
    ));

    expect($html)->toContain('class="lp-debug-timeline-bar is-short"')
        ->and($html)->toContain('data-lp-debug-tooltip="1.25 ms"')
        ->and($html)->toContain('tabindex="0"')
        ->and($html)->toContain('.lp-debug-bar .lp-debug-timeline-bar.is-short:after')
        ->and($html)->toContain('.lp-debug-bar .lp-debug-timeline-bar.is-short:focus:after,.lp-debug-bar .lp-debug-timeline-bar.is-short:hover:after');
});

it('renders the websocket tab before any websocket activity is recorded', function (): void {
    $html = new DebugBarRenderer()->render(new DiagnosticsSnapshot(
        groups: [],
        metrics: [],
        logs: [],
    ));

    preg_match('/data-lp-debug-tab="websocket"[^>]*>(.*?)<\/button>/s', $html, $websocket);
    preg_match('/data-lp-debug-panel="websocket"[^>]*>(.*?)<\/section>/s', $html, $websocketPanel);

    expect($websocket[1] ?? '')->toContain('<span>WebSocket</span>')
        ->and($websocket[1] ?? '')->toContain('<b>0</b>')
        ->and($websocketPanel[1] ?? '')->toContain('No websocket activity recorded.');
});

it('observes websocket activity without changing the application websocket url', function (): void {
    $html = new DebugBarRenderer()->render(new DiagnosticsSnapshot(
        groups: [],
        metrics: [],
        logs: [],
    ));

    expect($html)->toContain('new NativeWebSocket(url)')
        ->and($html)->toContain('recordRealtime("connect",String(url))')
        ->and($html)->toContain('function snapshotPanelState()')
        ->and($html)->toContain('function restorePanelState(state,activeId)')
        ->and($html)->toContain('restorePanelState(panelState,activeId)')
        ->and($html)->not->toContain('_lpwork_debug_session')
        ->and($html)->not->toContain('searchParams.set');
});

it('renders counts only for repeatable diagnostic tabs and keeps empty collections consistent', function (): void {
    $html = new DebugBarRenderer()->render(new DiagnosticsSnapshot(
        groups: [
            'Request' => [
                'Method' => 'GET',
                'URL' => 'http://example.test',
            ],
            'Route' => [
                'Name' => 'home',
                'Action' => 'HomeController@index',
                'Middleware' => [],
            ],
            'Events' => [],
            'Database' => [
                'Queries' => [],
            ],
            'Cache' => [
                'Operations' => [
                    ['Operation' => 'hit', 'Store' => 'framework', 'Key' => 'app'],
                ],
            ],
        ],
        metrics: [
            new Metric('http.request.duration', 3.4, 'ms', ['status' => 200], 10.0, 1024 * 1024),
        ],
        logs: [],
    ));

    preg_match('/data-lp-debug-tab="route"[^>]*>(.*?)<\/button>/s', $html, $route);
    preg_match('/data-lp-debug-tab="database"[^>]*>(.*?)<\/button>/s', $html, $database);
    preg_match('/data-lp-debug-tab="events"[^>]*>(.*?)<\/button>/s', $html, $events);
    preg_match('/data-lp-debug-panel="database"[^>]*>(.*?)<\/section>/s', $html, $databasePanel);
    preg_match('/data-lp-debug-panel="events"[^>]*>(.*?)<\/section>/s', $html, $eventsPanel);

    expect($route[1] ?? '')->not->toContain('<b>')
        ->and($html)->toContain('data-lp-debug-tab="route"><span>Route</span>')
        ->and($html)->toContain('data-lp-debug-tab="request"><span>Request</span>')
        ->and($database[1] ?? '')->toContain('<b>0</b>')
        ->and($events[1] ?? '')->toContain('<b>0</b>')
        ->and($html)->toContain('data-lp-debug-tab="cache"><span>Cache</span><b>1</b>')
        ->and($databasePanel[1] ?? '')->toContain('No database queries recorded.')
        ->and($databasePanel[1] ?? '')->toContain('<span>Record</span><span>Details</span>')
        ->and($databasePanel[1] ?? '')->not->toContain('<div class="lp-debug-field-name">Queries</div>')
        ->and($eventsPanel[1] ?? '')->toContain('No events recorded.')
        ->and($eventsPanel[1] ?? '')->toContain('<span>Record</span><span>Details</span>');
});

it('renders response security and throttle diagnostics as first class tabs', function (): void {
    $html = new DebugBarRenderer()->render(new DiagnosticsSnapshot(
        groups: [
            'Request' => [
                'Method' => 'POST',
                'URL' => 'http://example.test/upload',
                'Content length' => 128,
                'Body size bytes' => 128,
            ],
            'Response' => [
                'Captured' => true,
                'Status' => 429,
                'Content length header' => '21',
                'Body size bytes' => 21,
            ],
            'Security' => [
                'Denials' => [
                    ['Reason' => 'oversized_request_body', 'Message' => 'HTTP security denied oversized request body.'],
                ],
            ],
            'Throttle' => [
                'Denials' => [
                    ['Flow' => 'api', 'Context' => ['retry_after' => 60]],
                ],
            ],
        ],
        metrics: [
            new Metric('http.request.duration', 3.4, 'ms', ['status' => 429], 10.0, 1024 * 1024),
        ],
        logs: [],
    ));

    expect($html)->toContain('data-lp-debug-tab="response"><span>Response</span>')
        ->and($html)->toContain('Content length header')
        ->and($html)->toContain('Body size bytes')
        ->and($html)->toContain('data-lp-debug-tab="security"><span>Security</span><b>1</b>')
        ->and($html)->toContain('oversized_request_body')
        ->and($html)->toContain('data-lp-debug-tab="throttle"><span>Throttle</span><b>1</b>')
        ->and($html)->toContain('retry_after');
});

it('injects debug bar only into enabled html responses', function (): void {
    $context = new HttpDebugContext();
    $metrics = new MetricCollector();
    $diagnostics = new DiagnosticsCollector();
    $injector = new DebugBarResponseInjector(
        new DiagnosticsSnapshotFactory($context, $metrics, $diagnostics),
        new DebugBarRenderer(),
        new DebugBarRequestStore(sys_get_temp_dir() . '/lpwork-debugbar-test-' . bin2hex(random_bytes(4))),
        enabled: true,
    );

    $request = HttpRequest::fromArrays(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/']);
    $html = $injector->inject($request, HttpResponse::html('<html><body>Hello</body></html>'));
    $fragment = $injector->inject($request, HttpResponse::html('<h1>Hello</h1>'));
    $json = $injector->inject($request, HttpResponse::json(['ok' => true]));
    $disabled = new DebugBarResponseInjector(
        new DiagnosticsSnapshotFactory($context, $metrics, $diagnostics),
        new DebugBarRenderer(),
        new DebugBarRequestStore(sys_get_temp_dir() . '/lpwork-debugbar-test-disabled-' . bin2hex(random_bytes(4))),
        enabled: false,
    );

    expect($html->body())->toContain('id="lp-debug-bar"')
        ->and($html->header('X-LPWork-Debug-Id'))->not->toBeNull()
        ->and($html->body())->toContain('</body>')
        ->and($fragment->body())->not->toContain('lp-debug-bar')
        ->and($json->header('X-LPWork-Debug-Id'))->not->toBeNull()
        ->and($json->body())->not->toContain('lp-debug-bar')
        ->and($disabled->inject($request, HttpResponse::html('<body>Hello</body>'))->body())->not->toContain('lp-debug-bar');
});

it('does not fail the response when debugbar request storage is unavailable', function (): void {
    $path = sys_get_temp_dir() . '/lpwork-debugbar-store-file-' . bin2hex(random_bytes(4));
    file_put_contents($path, 'not a directory');

    $context = new HttpDebugContext();
    $metrics = new MetricCollector();
    $diagnostics = new DiagnosticsCollector();
    $injector = new DebugBarResponseInjector(
        new DiagnosticsSnapshotFactory($context, $metrics, $diagnostics),
        new DebugBarRenderer(),
        new DebugBarRequestStore($path),
        enabled: true,
    );

    try {
        $response = $injector->inject(
            HttpRequest::fromArrays(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/']),
            HttpResponse::html('<html><body>Hello</body></html>'),
        );
    } finally {
        unlink($path);
    }

    expect($response->body())->toContain('id="lp-debug-bar"')
        ->and($response->header('X-LPWork-Debug-Id'))->not->toBeNull();
});

it('stores debugbar request payloads under the application storage directory', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $app = new Application($environment->basePath());

    $app->register(new FoundationServiceProvider($app));
    $app->register(new DebugBarServiceProvider());

    $store = $app->container()->make(DebugBarRequestStore::class);

    expect($store)->toBeInstanceOf(DebugBarRequestStore::class);

    if (!$store instanceof DebugBarRequestStore) {
        return;
    }

    $store->put('session-one', 'request-one', ['label' => 'Current request']);

    expect($environment->basePath() . '/storage/framework/debugbar/sessions/session-one/request-one.json')->toBeFile()
        ->and(\Tests\support\ProjectPaths::root() . '/LPWork/storage/framework/debugbar/sessions/session-one/request-one.json')->not->toBeFile();
});

it('ignores corrupted debugbar request payloads during lookup and listing', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $store = new DebugBarRequestStore($environment->basePath() . '/storage/debugbar');

    $environment->writeFile('storage/debugbar/sessions/session-one/broken.json', '{broken json');
    $store->put('session-one', 'valid', ['label' => 'Valid request']);

    expect($store->get('session-one', 'broken'))->toBeNull()
        ->and($store->list('session-one'))->toHaveCount(1)
        ->and($store->list('session-one')[0]['label'])->toBe('Valid request');
});
