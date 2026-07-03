<?php

declare(strict_types=1);

use LPWork\DebugDump\Debug;
use LPWork\DebugDump\DebugDumper;
use LPWork\DebugDump\DebugDumpInspector;
use LPWork\DebugDump\DebugDumpRecord;
use LPWork\DebugDump\DebugDumpRenderer;
use LPWork\DebugDump\DebugDumpResponseInjector;
use LPWork\DebugDump\DebugDumpStore;
use LPWork\DebugDump\Exceptions\DumpAndDieException;
use LPWork\Responses\HttpResponse;
use LPWork\Routing\Router;
use LPWork\Support\Helpers;
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

it('inspects nested arrays and objects without leaking raw html', function (): void {
    $node = new DebugDumpInspector()->inspect([
        'name' => '<Ada>',
        'meta' => (object) ['active' => true],
    ]);

    $html = new DebugDumpRenderer()->overlay([
        new DebugDumpRecord('abc123', $node, 'User payload'),
    ]);

    expect($html)
        ->toContain('User payload')
        ->toContain('array(2)')
        ->toContain('is-branch')
        ->toContain('is-leaf')
        ->toContain('<details class="lp-dump-node is-branch"><summary>')
        ->toContain('data-lp-dump-dock')
        ->toContain("overlay.classList.add('is-collapsed')")
        ->toContain("overlay.classList.remove('is-collapsed')")
        ->toContain('.lp-dump-overlay:not(.is-collapsed) ~ #lp-debug-bar')
        ->toContain('.lp-dump-overlay.is-collapsed:has(~ #lp-debug-bar) .lp-dump-dock')
        ->toContain('data:image/svg+xml;base64,')
        ->toContain('&lt;Ada&gt;')
        ->toContain('stdClass');
    expect($html)->not->toContain('.lp-dump-overlay ~ #lp-debug-bar');
    expect(str_contains($html, '<details class="lp-dump-node is-branch" open>'))->toBeFalse();
    expect(str_contains($html, '<Ada>'))->toBeFalse();
});

it('collects non terminating dumps through the facade and helpers', function (): void {
    $store = new DebugDumpStore();
    $dumper = new DebugDumper(new DebugDumpInspector(), $store, enabled: true);
    Debug::setDumper($dumper);

    $value = ['answer' => 42];

    expect(Debug::label('first')->d($value))->toBe($value)
        ->and(Helpers::d('second', ['third' => true]))->toBe('second')
        ->and($store->all())->toHaveCount(2);
});

it('throws a dump and die exception for terminating dumps', function (): void {
    $store = new DebugDumpStore();
    Debug::setDumper(new DebugDumper(new DebugDumpInspector(), $store, enabled: true));

    expect(fn() => Debug::label('Stop here')->dd(['stop' => true]))
        ->toThrow(DumpAndDieException::class, 'Debug dump terminated the request.');
});

it('injects collected dumps into html responses and flushes the store', function (): void {
    $store = new DebugDumpStore();
    $dumper = new DebugDumper(new DebugDumpInspector(), $store, enabled: true);
    $injector = new DebugDumpResponseInjector($store, new DebugDumpRenderer(), enabled: true);

    $dumper->dumpLabeled('Payload', ['answer' => 42]);

    $response = $injector->inject(HttpResponse::html('<html><body><h1>Hello</h1></body></html>'));

    expect($response->body())
        ->toContain('lp-dump-overlay')
        ->toContain('Payload')
        ->toContain('answer')
        ->and($store->all())->toBe([]);
});

it('renders non terminating dumps as a dismissible overlay through http', function (): void {
    $harness = ApplicationTestHarness::fromProjectDefaults();
    $app = $harness->bootstrap();
    $router = $app->container()->make(Router::class);

    expect($router)->toBeInstanceOf(Router::class);

    if (!$router instanceof Router) {
        return;
    }

    $router->get('/debug-dump', function (): HttpResponse {
        Debug::label('Answer payload')->d(['answer' => 42]);

        return HttpResponse::html('<html><body><h1>Original page</h1></body></html>');
    });

    HttpTestClient::forApplication($app)
        ->get('/debug-dump')
        ->assertOk()
        ->assertSee('Original page')
        ->assertSee('lp-dump-overlay')
        ->assertSee('Answer payload')
        ->assertSee('answer');
});

it('renders terminating dumps as a full debug page through http', function (): void {
    $harness = ApplicationTestHarness::fromProjectDefaults();
    $app = $harness->bootstrap();
    $router = $app->container()->make(Router::class);

    expect($router)->toBeInstanceOf(Router::class);

    if (!$router instanceof Router) {
        return;
    }

    $router->get('/debug-die', function (): HttpResponse {
        Debug::label('Runtime object')->dd((object) ['name' => 'LPWork']);
    });

    HttpTestClient::forApplication($app)
        ->get('/debug-die')
        ->assertStatus(500)
        ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
        ->assertDontSee('Dump and die')
        ->assertDontSee('request stopped')
        ->assertDontSee('Request stopped at debug dump')
        ->assertSee('lp-dump-modal-page')
        ->assertSee('min-height: 100vh')
        ->assertSee('data-lp-debug-collapsed="1"')
        ->assertSee('<button type="button" class="lp-debug-dock"')
        ->assertSee('Runtime object')
        ->assertSee('LPWork')
        ->assertDontSee('Unreachable');
});
