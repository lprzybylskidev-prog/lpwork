<?php

declare(strict_types=1);

use LPWork\ErrorHandling\Renderers\HttpProductionExceptionRenderer;
use LPWork\Http\Exceptions\MethodNotAllowedException;
use LPWork\Http\Exceptions\NotFoundException;

it('renders production exceptions as Http responses', function (): void {
    $response = new HttpProductionExceptionRenderer()->render(new RuntimeException('Boom with APP_KEY=secret'));

    expect($response->statusCode())->toBe(500)
        ->and($response->headers())->toBe(['Content-Type' => 'text/html; charset=UTF-8'])
        ->and($response->body())->toContain('Production response')
        ->and($response->body())->toContain('Server error')
        ->and($response->body())->toContain('class="lp-ui-body"')
        ->and($response->body())->toContain('lp-ui-status-page--error')
        ->and($response->body())->toContain('class="lp-ui-status-main"')
        ->and($response->body())->toContain('/assets/lpwork-logo.svg?v=')
        ->and($response->body())->toContain('LPWORK')
        ->and($response->body())->toContain('Production response')
        ->and($response->body())->toContain('/favicon.svg?v=')
        ->and($response->body())->toContain('class="lp-ui-status-code">500</p>')
        ->and($response->body())->not->toContain('class="lp-ui-status-details"')
        ->and($response->body())->not->toContain('Diagnostics')
        ->and($response->body())->not->toContain('Boom')
        ->and($response->body())->not->toContain('APP_KEY')
        ->and($response->body())->not->toContain('Stack trace');
});

it('renders Http exceptions with their status code and headers in production', function (): void {
    $notFound = new HttpProductionExceptionRenderer()->render(new NotFoundException());
    $methodNotAllowed = new HttpProductionExceptionRenderer()->render(new MethodNotAllowedException(['GET', 'POST']));

    expect($notFound->statusCode())->toBe(404)
        ->and($notFound->body())->toContain('class="lp-ui-status-code">404</p>')
        ->and($notFound->headers())->toBe(['Content-Type' => 'text/html; charset=UTF-8'])
        ->and($methodNotAllowed->statusCode())->toBe(405)
        ->and($methodNotAllowed->body())->toContain('class="lp-ui-status-code">405</p>')
        ->and($methodNotAllowed->headers())->toBe([
            'Content-Type' => 'text/html; charset=UTF-8',
            'Allow' => 'GET, POST',
        ]);
});
