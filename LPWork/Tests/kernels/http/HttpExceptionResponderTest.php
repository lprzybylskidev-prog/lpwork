<?php

declare(strict_types=1);

use LPWork\ErrorHandling\Contracts\ExceptionReporter;
use LPWork\ErrorHandling\Contracts\HttpExceptionRenderer;
use LPWork\ErrorHandling\HttpExceptionHandler;
use LPWork\ErrorHandling\Renderers\HttpProductionExceptionRenderer;
use LPWork\Kernels\Http\HttpExceptionResponder;
use LPWork\Responses\HttpResponse;

it('uses the registered Http exception handler when available', function (): void {
    $handler = new HttpExceptionHandler(
        new class implements ExceptionReporter {
            public function report(Throwable $throwable): void {}
        },
        new class implements HttpExceptionRenderer {
            public function render(Throwable $throwable): HttpResponse
            {
                return HttpResponse::text('handled', statusCode: 418);
            }
        },
    );

    $response = new HttpExceptionResponder($handler, new HttpProductionExceptionRenderer())
        ->respond(new Exception('Handled exception'));

    expect($response->statusCode())->toBe(418)
        ->and($response->body())->toBe('handled');
});

it('falls back to the production renderer before bootstrap registers a handler', function (): void {
    $response = new HttpExceptionResponder(null, new HttpProductionExceptionRenderer())
        ->respond(new Exception('Fallback exception'));

    expect($response->statusCode())->toBe(500)
        ->and($response->headers())->toBe(['Content-Type' => 'text/html; charset=UTF-8'])
        ->and($response->body())->toContain('Server error')
        ->and($response->body())->toContain('class="lp-ui-status-code">500</p>');
});
