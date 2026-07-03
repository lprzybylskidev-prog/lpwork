<?php

declare(strict_types=1);

use LPWork\Http\Cookie;
use LPWork\Http\Exceptions\BadRequestException;
use LPWork\Http\Exceptions\ForbiddenException;
use LPWork\Http\Exceptions\PayloadTooLargeException;
use LPWork\Logging\Enums\LogLevel;
use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;
use LPWork\Security\Events\HttpSecurityDenied;
use LPWork\Security\Http\HttpSecurityMiddleware;
use LPWork\Security\Listeners\RecordHttpSecurityDenied;
use LPWork\Security\SecurityDebugCollector;
use LPWork\Security\SecurityDebugContextProvider;
use Tests\support\events\EventDispatcherFactory;
use Tests\support\security\SecurityConfigs;
use Tests\support\testing\Logging\TestLogDriver;

it('rejects requests for untrusted hosts', function (): void {
    $middleware = new HttpSecurityMiddleware(SecurityConfigs::http(trustedHosts: ['example.com']));

    expect(fn() => $middleware->handle(
        HttpRequest::fromArrays([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'HTTP_HOST' => 'evil.test',
        ]),
        static fn(): HttpResponse => HttpResponse::text('ok'),
    ))->toThrow(BadRequestException::class, 'Untrusted HTTP host.');
});

it('logs HTTP security denials', function (): void {
    $driver = new TestLogDriver();
    $middleware = new HttpSecurityMiddleware(
        SecurityConfigs::http(trustedHosts: ['example.test']),
        EventDispatcherFactory::withLogger($driver),
    );

    expect(fn() => $middleware->handle(
        HttpRequest::fromArrays([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'HTTP_HOST' => 'evil.test',
        ]),
        static fn(): HttpResponse => HttpResponse::text('ok'),
    ))->toThrow(BadRequestException::class);

    $record = $driver->records()[0];

    expect($record->level)->toBe(LogLevel::Warning)
        ->and($record->message)->toBe('HTTP security denied untrusted host.')
        ->and($record->context['host'])->toBe('evil.test');
});

it('records HTTP security denials for debug diagnostics', function (): void {
    $collector = new SecurityDebugCollector();
    $listener = new RecordHttpSecurityDenied($collector);
    $middleware = new HttpSecurityMiddleware(
        SecurityConfigs::http(trustedHosts: ['example.test']),
        EventDispatcherFactory::withListener($listener, HttpSecurityDenied::class),
    );

    expect(fn() => $middleware->handle(
        HttpRequest::fromArrays([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'HTTP_HOST' => 'evil.test',
        ]),
        static fn(): HttpResponse => HttpResponse::text('ok'),
    ))->toThrow(BadRequestException::class);

    expect(new SecurityDebugContextProvider($collector)->context(new \LPWork\ErrorHandling\HttpDebugContext()))
        ->toBe([
            'Security' => [
                'Denials' => [
                    [
                        'Reason' => 'untrusted_host',
                        'Message' => 'HTTP security denied untrusted host.',
                        'Context' => [
                            'host' => 'evil.test',
                            'path' => '/',
                            'client_ip' => '',
                        ],
                    ],
                ],
            ],
        ]);
});

it('trusts forwarded hosts only from trusted proxies', function (): void {
    $middleware = new HttpSecurityMiddleware(SecurityConfigs::http(
        trustedHosts: ['example.com'],
        trustedProxies: ['10.0.0.1'],
    ));

    $response = $middleware->handle(
        HttpRequest::fromArrays([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'HTTP_HOST' => 'proxy.internal',
            'HTTP_X_FORWARDED_HOST' => 'example.com',
            'REMOTE_ADDR' => '10.0.0.1',
        ]),
        static fn(): HttpResponse => HttpResponse::text('ok'),
    );

    expect($response->body())->toBe('ok');

    expect(fn() => $middleware->handle(
        HttpRequest::fromArrays([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'HTTP_HOST' => 'proxy.internal',
            'HTTP_X_FORWARDED_HOST' => 'example.com',
            'REMOTE_ADDR' => '10.0.0.2',
        ]),
        static fn(): HttpResponse => HttpResponse::text('ok'),
    ))->toThrow(BadRequestException::class);
});

it('matches trusted host patterns and trusted proxy CIDR ranges', function (): void {
    $middleware = new HttpSecurityMiddleware(SecurityConfigs::http(
        trustedHosts: ['*.example.com'],
        trustedProxies: ['10.0.0.0/24'],
    ));

    $response = $middleware->handle(
        HttpRequest::fromArrays([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'HTTP_HOST' => 'proxy.internal',
            'HTTP_X_FORWARDED_HOST' => 'app.example.com',
            'REMOTE_ADDR' => '10.0.0.55',
        ]),
        static fn(): HttpResponse => HttpResponse::text('ok'),
    );

    expect($response->body())->toBe('ok');

    expect(fn() => $middleware->handle(
        HttpRequest::fromArrays([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'HTTP_HOST' => 'proxy.internal',
            'HTTP_X_FORWARDED_HOST' => 'app.example.com',
            'REMOTE_ADDR' => '10.0.1.55',
        ]),
        static fn(): HttpResponse => HttpResponse::text('ok'),
    ))->toThrow(BadRequestException::class);
});

it('requires HTTPS when configured', function (): void {
    $middleware = new HttpSecurityMiddleware(SecurityConfigs::http(enforceHttps: true));

    expect(fn() => $middleware->handle(
        HttpRequest::fromArrays([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'HTTP_HOST' => 'example.com',
        ]),
        static fn(): HttpResponse => HttpResponse::text('ok'),
    ))->toThrow(ForbiddenException::class, 'HTTPS is required.');
});

it('allows local HTTP flows when local flows are enabled', function (): void {
    $middleware = new HttpSecurityMiddleware(SecurityConfigs::http(
        allowLocalFlows: true,
        enforceHttps: true,
    ));

    $response = $middleware->handle(
        HttpRequest::fromArrays([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'HTTP_HOST' => 'localhost',
        ]),
        static fn(): HttpResponse => HttpResponse::text('ok'),
    );

    expect($response->body())->toBe('ok');
});

it('uses forwarded HTTPS only from trusted proxies', function (): void {
    $middleware = new HttpSecurityMiddleware(SecurityConfigs::http(
        enforceHttps: true,
        trustedProxies: ['10.0.0.1'],
    ));

    $response = $middleware->handle(
        HttpRequest::fromArrays([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'REMOTE_ADDR' => '10.0.0.1',
        ]),
        static fn(): HttpResponse => HttpResponse::text('ok'),
    );

    expect($response->body())->toBe('ok');

    expect(fn() => $middleware->handle(
        HttpRequest::fromArrays([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'REMOTE_ADDR' => '10.0.0.2',
        ]),
        static fn(): HttpResponse => HttpResponse::text('ok'),
    ))->toThrow(ForbiddenException::class);
});

it('rejects oversized request bodies and uploads', function (): void {
    $middleware = new HttpSecurityMiddleware(SecurityConfigs::http(
        maxRequestBodyBytes: 10,
        maxUploadBytes: 10,
    ));

    expect(fn() => $middleware->handle(
        HttpRequest::fromArrays([
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/',
            'CONTENT_LENGTH' => '11',
        ]),
        static fn(): HttpResponse => HttpResponse::text('ok'),
    ))->toThrow(PayloadTooLargeException::class, 'HTTP request body is too large.');

    expect(fn() => $middleware->handle(
        HttpRequest::fromArrays(
            server: [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/',
            ],
            files: [
                'avatar' => [
                    'tmp_name' => '/tmp/avatar',
                    'name' => 'avatar.png',
                    'type' => 'image/png',
                    'size' => 11,
                    'error' => UPLOAD_ERR_OK,
                ],
            ],
        ),
        static fn(): HttpResponse => HttpResponse::text('ok'),
    ))->toThrow(PayloadTooLargeException::class, 'HTTP upload is too large.');
});

it('adds configured security headers without overriding response headers', function (): void {
    $middleware = new HttpSecurityMiddleware(SecurityConfigs::http(
        enforceHttps: true,
        sendSecurityHeaders: true,
        headers: [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
        ],
    ));

    $response = $middleware->handle(
        HttpRequest::fromArrays([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'HTTPS' => 'on',
        ]),
        static fn(): HttpResponse => HttpResponse::text('ok', headers: ['X-Frame-Options' => 'DENY']),
    );

    expect($response->headers())->toBe([
        'Content-Type' => 'text/plain; charset=UTF-8',
        'X-Frame-Options' => 'DENY',
        'X-Content-Type-Options' => 'nosniff',
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
    ]);
});

it('preserves cookies and streams when adding configured security headers', function (): void {
    $middleware = new HttpSecurityMiddleware(SecurityConfigs::http(
        sendSecurityHeaders: true,
        headers: ['X-Content-Type-Options' => 'nosniff'],
    ));

    $response = $middleware->handle(
        HttpRequest::fromArrays([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
        ]),
        static fn(): HttpResponse => HttpResponse::stream(static function (mixed $output): void {
            fwrite($output, 'streamed');
        })->withCookie(new Cookie('theme', 'dark')),
    );

    expect($response->header('X-Content-Type-Options'))->toBe('nosniff')
        ->and($response->cookies())->toHaveCount(1)
        ->and($response->cookies()[0]->toHeader())->toBe('theme=dark; Path=/; SameSite=Lax; HttpOnly')
        ->and($response->streamCallback())->not->toBeNull();
});
