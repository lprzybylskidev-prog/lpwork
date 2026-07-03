<?php

declare(strict_types=1);

use LPWork\ErrorHandling\Renderers\JsonHttpExceptionRenderer;
use LPWork\Http\Exceptions\BadRequestException;
use LPWork\Http\Exceptions\ConflictException;
use LPWork\Http\Exceptions\ForbiddenException;
use LPWork\Http\Exceptions\GoneException;
use LPWork\Http\Exceptions\MethodNotAllowedException;
use LPWork\Http\Exceptions\NotFoundException;
use LPWork\Http\Exceptions\PayloadTooLargeException;
use LPWork\Http\Exceptions\ServiceUnavailableException;
use LPWork\Http\Exceptions\TooManyRequestsException;
use LPWork\Http\Exceptions\UnauthorizedException;
use LPWork\Http\Exceptions\UnprocessableEntityException;

it('renders production exceptions as sanitized JSON Http responses', function (): void {
    $response = new JsonHttpExceptionRenderer()->render(new RuntimeException('Boom with APP_KEY=secret'));

    expect($response->statusCode())->toBe(500)
        ->and($response->headers())->toBe(['Content-Type' => 'application/json; charset=UTF-8'])
        ->and($response->body())->toBe('{"error":{"status":500,"message":"Internal Server Error"}}')
        ->and($response->body())->not->toContain('Boom')
        ->and($response->body())->not->toContain('APP_KEY')
        ->and($response->body())->not->toContain('Stack trace');
});

it('renders Http exceptions as sanitized JSON responses with their status code', function (): void {
    $response = new JsonHttpExceptionRenderer()->render(new NotFoundException('Missing secret page'));

    expect($response->statusCode())->toBe(404)
        ->and($response->headers())->toBe(['Content-Type' => 'application/json; charset=UTF-8'])
        ->and($response->body())->toBe('{"error":{"status":404,"message":"Not Found"}}')
        ->and($response->body())->not->toContain('Missing secret page');
});

it('renders known HTTP status messages and preserves exception headers', function (): void {
    $renderer = new JsonHttpExceptionRenderer();

    $responses = [
        $renderer->render(new BadRequestException()),
        $renderer->render(new UnauthorizedException()),
        $renderer->render(new ForbiddenException()),
        $renderer->render(new MethodNotAllowedException(['GET', 'POST'])),
        $renderer->render(new ConflictException()),
        $renderer->render(new GoneException()),
        $renderer->render(new UnprocessableEntityException()),
        $renderer->render(TooManyRequestsException::withRetryAfter('30')),
        $renderer->render(new PayloadTooLargeException()),
        $renderer->render(ServiceUnavailableException::withRetryAfter('60')),
    ];

    expect(array_map(static fn(LPWork\Responses\HttpResponse $response): string => $response->body(), $responses))
        ->toBe([
            '{"error":{"status":400,"message":"Bad Request"}}',
            '{"error":{"status":401,"message":"Unauthorized"}}',
            '{"error":{"status":403,"message":"Forbidden"}}',
            '{"error":{"status":405,"message":"Method Not Allowed"}}',
            '{"error":{"status":409,"message":"Conflict"}}',
            '{"error":{"status":410,"message":"Gone"}}',
            '{"error":{"status":422,"message":"Unprocessable Entity"}}',
            '{"error":{"status":429,"message":"Too Many Requests"}}',
            '{"error":{"status":413,"message":"Internal Server Error"}}',
            '{"error":{"status":503,"message":"Service Unavailable"}}',
        ])
        ->and($responses[3]->header('Allow'))->toBe('GET, POST')
        ->and($responses[7]->header('Retry-After'))->toBe('30')
        ->and($responses[9]->header('Retry-After'))->toBe('60');
});
