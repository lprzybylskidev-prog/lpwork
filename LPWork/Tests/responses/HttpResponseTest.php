<?php

declare(strict_types=1);

use LPWork\Http\Cookie;
use LPWork\Responses\Contracts\Response;
use LPWork\Responses\Exceptions\InvalidRedirectStatusException;
use LPWork\Responses\HttpResponse;

it('stores Http response body status and headers', function (): void {
    $response = new HttpResponse(
        body: 'Created',
        statusCode: 201,
        headers: ['Location' => '/articles/42'],
    );

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->body())->toBe('Created')
        ->and($response->statusCode())->toBe(201)
        ->and($response->headers())->toBe(['Location' => '/articles/42'])
        ->and($response->header('Location'))->toBe('/articles/42')
        ->and($response->header('location'))->toBe('/articles/42')
        ->and($response->header('Missing', 'fallback'))->toBe('fallback');
});

it('normalizes response header names', function (): void {
    $response = new HttpResponse(
        headers: [
            'content_type' => 'application/json',
            'x-frame-options' => 'DENY',
        ],
    );

    expect($response->headers())->toBe([
        'Content-Type' => 'application/json',
        'X-Frame-Options' => 'DENY',
    ])
        ->and($response->header('CONTENT-TYPE'))->toBe('application/json')
        ->and($response->header('x_frame_options'))->toBe('DENY');
});

it('creates text and html responses', function (): void {
    expect(HttpResponse::text('Plain')->headers())->toBe(['Content-Type' => 'text/plain; charset=UTF-8'])
        ->and(HttpResponse::html('<h1>Hello</h1>')->headers())->toBe(['Content-Type' => 'text/html; charset=UTF-8']);
});

it('creates json responses', function (): void {
    $response = HttpResponse::json(['created' => true], statusCode: 201);

    expect($response->body())->toBe('{"created":true}')
        ->and($response->statusCode())->toBe(201)
        ->and($response->headers())->toBe(['Content-Type' => 'application/json; charset=UTF-8']);
});

it('creates redirects no content responses and immutable response variants', function (): void {
    $response = HttpResponse::redirect('/dashboard')
        ->withStatus(303)
        ->withHeader('x-flow', 'web')
        ->withBody('Redirecting');

    expect($response->statusCode())->toBe(303)
        ->and($response->header('Location'))->toBe('/dashboard')
        ->and($response->header('X-Flow'))->toBe('web')
        ->and($response->body())->toBe('Redirecting')
        ->and($response->withoutHeader('X-Flow')->header('X-Flow'))->toBeNull()
        ->and(HttpResponse::noContent()->statusCode())->toBe(204)
        ->and(HttpResponse::noContent()->body())->toBe('')
        ->and(HttpResponse::created('/articles/1')->statusCode())->toBe(201)
        ->and(HttpResponse::created('/articles/1')->header('Location'))->toBe('/articles/1');
});

it('rejects invalid redirect status codes', function (): void {
    expect(fn(): HttpResponse => HttpResponse::redirect('/dashboard', 200))
        ->toThrow(InvalidRedirectStatusException::class, 'HTTP redirect status code is invalid: 200.');
});

it('stores cookies on HTTP responses', function (): void {
    $response = HttpResponse::text('ok')
        ->withCookie(new Cookie('theme', 'dark', maxAge: 60, secure: true))
        ->withoutCookie('theme', '/settings', 'example.com');

    expect($response->cookies())->toHaveCount(1)
        ->and($response->cookies()[0]->toHeader())->toBe('theme=; Path=/settings; SameSite=Lax; Max-Age=-3600; Expires=Thu, 01 Jan 1970 00:00:00 GMT; Domain=example.com; HttpOnly');
});

it('creates file download and stream responses', function (): void {
    $file = tempnam(sys_get_temp_dir(), 'lpwork-response-');

    if ($file === false) {
        throw new RuntimeException('Could not create temporary response file.');
    }

    try {
        file_put_contents($file, 'exported');

        $download = HttpResponse::download($file, 'report.txt');
        $stream = HttpResponse::stream(static function (mixed $output): void {
            fwrite($output, 'streamed');
        });

        expect($download->header('Content-Length'))->toBe('8')
            ->and($download->header('Content-Type'))->toBe('application/octet-stream')
            ->and($download->header('Content-Disposition'))->toBe('attachment; filename="report.txt"')
            ->and($download->streamCallback())->not->toBeNull()
            ->and($stream->streamCallback())->not->toBeNull();
    } finally {
        unlink($file);
    }
});

it('preserves stream callbacks when changing response body', function (): void {
    $response = HttpResponse::stream(static function (mixed $output): void {
        fwrite($output, 'streamed');
    })->withBody('fallback');

    expect($response->body())->toBe('fallback')
        ->and($response->streamCallback())->not->toBeNull();
});
