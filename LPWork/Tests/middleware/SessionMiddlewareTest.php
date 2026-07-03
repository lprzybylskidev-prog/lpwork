<?php

declare(strict_types=1);

use LPWork\Cache\CacheStore;
use LPWork\Middleware\SessionMiddleware;
use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;
use LPWork\Session\Drivers\CacheSessionDriver;
use Tests\support\session\InMemorySessionDriver;
use Tests\support\testing\Cache\InMemoryCacheDriver;

it('attaches session to the request and saves it after response', function (): void {
    $driver = new InMemorySessionDriver(['user_id' => 15]);
    $middleware = new SessionMiddleware($driver);

    $response = $middleware->handle(
        HttpRequest::fromArrays([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/profile',
        ]),
        static function (HttpRequest $request): HttpResponse {
            expect($request->session()->get('user_id'))->toBe(15);

            $request->session()->put('visited', true);

            return HttpResponse::text('ok');
        },
    );

    expect($response->body())->toBe('ok')
        ->and($driver->starts)->toBe(1)
        ->and($driver->saves)->toBe(1)
        ->and($driver->data())->toHaveKey('visited', true);
});

it('ages flash data before the next middleware runs', function (): void {
    $driver = new InMemorySessionDriver([
        'status' => 'Saved',
        '_flash' => [
            'new' => ['status'],
            'old' => [],
        ],
    ]);

    $middleware = new SessionMiddleware($driver);

    $middleware->handle(
        HttpRequest::fromArrays([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
        ]),
        static function (HttpRequest $request): HttpResponse {
            expect($request->session()->get('status'))->toBe('Saved');

            return HttpResponse::text('ok');
        },
    );

    expect($driver->data())->toMatchArray([
        'status' => 'Saved',
        '_flash' => [
            'new' => [],
            'old' => ['status'],
        ],
    ]);

    $middleware->handle(
        HttpRequest::fromArrays([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
        ]),
        static function (HttpRequest $request): HttpResponse {
            expect($request->session()->get('status', 'missing'))->toBe('missing');

            return HttpResponse::text('ok');
        },
    );

    expect($driver->data())->not->toHaveKey('status');
});

it('saves session when next middleware throws', function (): void {
    $driver = new InMemorySessionDriver();
    $middleware = new SessionMiddleware($driver);

    expect(fn() => $middleware->handle(
        HttpRequest::fromArrays([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
        ]),
        static function (HttpRequest $request): HttpResponse {
            $request->session()->put('attempted', true);

            throw new RuntimeException('Stop pipeline.');
        },
    ))->toThrow(RuntimeException::class)
        ->and($driver->saves)->toBe(1)
        ->and($driver->data())->toHaveKey('attempted', true);
});

it('passes request cookies to persistent session drivers and queues the response cookie', function (): void {
    $cache = new CacheStore('sessions', new InMemoryCacheDriver());
    $driver = new CacheSessionDriver($cache, name: 'LPWORK_SESSION', lifetime: 120);
    $middleware = new SessionMiddleware($driver);
    $sessionId = str_repeat('a', 64);

    $cache->put('sessions:' . $sessionId, ['user_id' => 15], 7200);

    $response = $middleware->handle(
        HttpRequest::fromArrays(
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/profile'],
            cookies: ['LPWORK_SESSION' => $sessionId],
        ),
        static function (HttpRequest $request): HttpResponse {
            expect($request->session()->get('user_id'))->toBe(15);

            $request->session()->put('visited', true);

            return HttpResponse::text('ok');
        },
    );

    expect($cache->get('sessions:' . $sessionId))->toHaveKey('visited', true)
        ->and($response->cookies())->toHaveCount(1)
        ->and($response->cookies()[0]->toHeader())->toContain('LPWORK_SESSION=' . $sessionId);
});
