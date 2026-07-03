<?php

declare(strict_types=1);

use LPWork\Http\Exceptions\TooManyRequestsException;
use LPWork\Logging\Enums\LogLevel;
use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;
use LPWork\Throttle\Events\HttpRequestThrottled;
use LPWork\Throttle\HttpThrottleMiddleware;
use LPWork\Throttle\Listeners\RecordHttpRequestThrottled;
use LPWork\Throttle\Storage\InMemoryThrottleStorage;
use LPWork\Throttle\ThrottleDebugCollector;
use LPWork\Throttle\ThrottleDebugContextProvider;
use LPWork\Throttle\ThrottleLimiter;
use LPWork\Throttle\ThrottlePolicy;
use Tests\support\events\EventDispatcherFactory;
use Tests\support\testing\Logging\TestLogDriver;
use Tests\support\throttle\MutableThrottleClock;

it('adds rate limit headers to allowed HTTP responses', function (): void {
    $middleware = new HttpThrottleMiddleware(
        new ThrottleLimiter(new InMemoryThrottleStorage(), new MutableThrottleClock()),
        new ThrottlePolicy('http_web', true, 2, 60),
        'web',
    );

    $response = $middleware->handle(
        HttpRequest::fromArrays([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'REMOTE_ADDR' => '127.0.0.1',
        ]),
        static fn(): HttpResponse => HttpResponse::text('ok'),
    );

    expect($response->body())->toBe('ok')
        ->and($response->header('X-RateLimit-Limit'))->toBe('2')
        ->and($response->header('X-RateLimit-Remaining'))->toBe('1');
});

it('rejects HTTP requests after the configured limit', function (): void {
    $middleware = new HttpThrottleMiddleware(
        new ThrottleLimiter(new InMemoryThrottleStorage(), new MutableThrottleClock()),
        new ThrottlePolicy('http_api', true, 1, 60),
        'api',
    );
    $request = HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/api',
        'REMOTE_ADDR' => '127.0.0.1',
    ]);

    $middleware->handle($request, static fn(): HttpResponse => HttpResponse::json(['ok' => true]));

    expect(fn() => $middleware->handle($request, static fn(): HttpResponse => HttpResponse::json(['ok' => true])))
        ->toThrow(TooManyRequestsException::class, 'Too many requests.');
});

it('logs throttled HTTP requests', function (): void {
    $driver = new TestLogDriver();
    $middleware = new HttpThrottleMiddleware(
        new ThrottleLimiter(new InMemoryThrottleStorage(), new MutableThrottleClock()),
        new ThrottlePolicy('http_api', true, 1, 60),
        'api',
        EventDispatcherFactory::withLogger($driver),
    );
    $request = HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/api',
        'REMOTE_ADDR' => '127.0.0.1',
    ]);

    $middleware->handle($request, static fn(): HttpResponse => HttpResponse::json(['ok' => true]));

    expect(fn() => $middleware->handle($request, static fn(): HttpResponse => HttpResponse::json(['ok' => true])))
        ->toThrow(TooManyRequestsException::class);

    $record = $driver->records()[0];

    expect($record->level)->toBe(LogLevel::Warning)
        ->and($record->message)->toBe('HTTP request throttled.')
        ->and($record->context['flow'])->toBe('api')
        ->and($record->context['retry_after'])->toBe(60);
});

it('records throttled HTTP requests for debug diagnostics', function (): void {
    $collector = new ThrottleDebugCollector();
    $middleware = new HttpThrottleMiddleware(
        new ThrottleLimiter(new InMemoryThrottleStorage(), new MutableThrottleClock()),
        new ThrottlePolicy('http_api', true, 1, 60),
        'api',
        EventDispatcherFactory::withListener(new RecordHttpRequestThrottled($collector), HttpRequestThrottled::class),
    );
    $request = HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/api',
        'REMOTE_ADDR' => '127.0.0.1',
    ]);

    $middleware->handle($request, static fn(): HttpResponse => HttpResponse::json(['ok' => true]));

    expect(fn() => $middleware->handle($request, static fn(): HttpResponse => HttpResponse::json(['ok' => true])))
        ->toThrow(TooManyRequestsException::class);

    expect(new ThrottleDebugContextProvider($collector)->context(new \LPWork\ErrorHandling\HttpDebugContext()))
        ->toBe([
            'Throttle' => [
                'Denials' => [
                    [
                        'Flow' => 'api',
                        'Context' => [
                            'flow' => 'api',
                            'path' => '/api',
                            'key' => 'http:api:127.0.0.1',
                            'attempts' => 2,
                            'max_attempts' => 1,
                            'retry_after' => 60,
                        ],
                    ],
                ],
            ],
        ]);
});
