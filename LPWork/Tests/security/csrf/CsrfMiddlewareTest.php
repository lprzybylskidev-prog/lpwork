<?php

declare(strict_types=1);

use LPWork\Http\Exceptions\ForbiddenException;
use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;
use LPWork\Security\Csrf\CsrfMiddleware;
use LPWork\Security\Csrf\CsrfTokenManager;
use LPWork\Session\Session;
use Tests\support\security\SecurityConfigs;

it('creates a session token while reading web requests', function (): void {
    $config = SecurityConfigs::http(csrfEnabled: true)->csrf();
    $middleware = new CsrfMiddleware($config, new CsrfTokenManager($config));
    $session = new Session();

    $response = $middleware->handle(
        HttpRequest::fromArrays([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/form',
        ])->withSession($session),
        static fn(): HttpResponse => HttpResponse::text('form'),
    );

    expect($response->body())->toBe('form')
        ->and($session->get('_csrf_token'))->toBeString()
        ->and($session->get('_csrf_token'))->not->toBe('');
});

it('accepts valid CSRF tokens from input or headers', function (): void {
    $config = SecurityConfigs::http(csrfEnabled: true)->csrf();
    $tokens = new CsrfTokenManager($config);
    $middleware = new CsrfMiddleware($config, $tokens);
    $session = new Session();
    $token = $tokens->token($session);

    $inputResponse = $middleware->handle(
        HttpRequest::fromArrays(
            server: [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/profile',
            ],
            input: ['_token' => $token],
        )->withSession($session),
        static fn(): HttpResponse => HttpResponse::text('input'),
    );

    $headerResponse = $middleware->handle(
        HttpRequest::fromArrays([
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/profile',
            'HTTP_X_CSRF_TOKEN' => $token,
        ])->withSession($session),
        static fn(): HttpResponse => HttpResponse::text('header'),
    );

    expect($inputResponse->body())->toBe('input')
        ->and($headerResponse->body())->toBe('header');
});

it('rotates CSRF tokens after successful validation when configured', function (): void {
    $config = SecurityConfigs::http(csrfEnabled: true, csrfRotate: true)->csrf();
    $tokens = new CsrfTokenManager($config);
    $middleware = new CsrfMiddleware($config, $tokens);
    $session = new Session();
    $token = $tokens->token($session);

    $middleware->handle(
        HttpRequest::fromArrays(
            server: [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/profile',
            ],
            input: ['_token' => $token],
        )->withSession($session),
        static fn(): HttpResponse => HttpResponse::text('ok'),
    );

    expect($session->get('_csrf_token'))->toBeString()
        ->and($session->get('_csrf_token'))->not->toBe($token);
});

it('accepts per-form CSRF tokens when configured', function (): void {
    $config = SecurityConfigs::http(csrfEnabled: true, csrfPerForm: true)->csrf();
    $tokens = new CsrfTokenManager($config);
    $middleware = new CsrfMiddleware($config, $tokens);
    $session = new Session();
    $token = $tokens->formToken($session, 'profile');

    $response = $middleware->handle(
        HttpRequest::fromArrays(
            server: [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/profile',
            ],
            input: [
                '_token_form' => 'profile',
                '_token' => $token,
            ],
        )->withSession($session),
        static fn(): HttpResponse => HttpResponse::text('ok'),
    );

    expect($response->body())->toBe('ok');
});

it('rejects unsafe requests with missing or invalid CSRF tokens', function (): void {
    $config = SecurityConfigs::http(csrfEnabled: true)->csrf();
    $tokens = new CsrfTokenManager($config);
    $middleware = new CsrfMiddleware($config, $tokens);
    $session = new Session();
    $tokens->token($session);

    expect(fn() => $middleware->handle(
        HttpRequest::fromArrays([
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/profile',
        ])->withSession($session),
        static fn(): HttpResponse => HttpResponse::text('ok'),
    ))->toThrow(ForbiddenException::class, 'Invalid CSRF token.');

    expect(fn() => $middleware->handle(
        HttpRequest::fromArrays(
            server: [
                'REQUEST_METHOD' => 'DELETE',
                'REQUEST_URI' => '/profile',
            ],
            input: ['_token' => 'invalid'],
        )->withSession($session),
        static fn(): HttpResponse => HttpResponse::text('ok'),
    ))->toThrow(ForbiddenException::class, 'Invalid CSRF token.');
});

it('bypasses validation when CSRF is disabled', function (): void {
    $config = SecurityConfigs::http(csrfEnabled: false)->csrf();
    $middleware = new CsrfMiddleware($config, new CsrfTokenManager($config));

    $response = $middleware->handle(
        HttpRequest::fromArrays([
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/profile',
        ])->withSession(new Session()),
        static fn(): HttpResponse => HttpResponse::text('ok'),
    );

    expect($response->body())->toBe('ok');
});
