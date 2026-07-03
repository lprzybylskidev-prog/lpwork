<?php

declare(strict_types=1);

use LPWork\ErrorHandling\Contracts\ExceptionReporter;
use LPWork\ErrorHandling\Contracts\HttpExceptionRenderer;
use LPWork\ErrorHandling\HttpExceptionHandler;
use LPWork\Responses\HttpResponse;

it('reports and renders Http exception responses', function (): void {
    $throwable = new RuntimeException('Boom');

    $reporter = new class implements ExceptionReporter {
        public ?Throwable $throwable = null;

        public function report(Throwable $throwable): void
        {
            $this->throwable = $throwable;
        }
    };

    $renderer = new class implements HttpExceptionRenderer {
        public ?Throwable $throwable = null;

        public function render(Throwable $throwable): HttpResponse
        {
            $this->throwable = $throwable;

            return HttpResponse::text('rendered exception', statusCode: 500);
        }
    };

    $response = new HttpExceptionHandler($reporter, $renderer)->handle($throwable);

    expect($reporter->throwable)->toBe($throwable)
        ->and($renderer->throwable)->toBe($throwable)
        ->and($response->statusCode())->toBe(500)
        ->and($response->body())->toBe('rendered exception');
});
