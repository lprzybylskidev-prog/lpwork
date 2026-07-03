<?php

declare(strict_types=1);

use LPWork\ErrorHandling\CliExceptionHandler;
use LPWork\ErrorHandling\Contracts\ExceptionRenderer;
use LPWork\ErrorHandling\Contracts\ExceptionReporter;

it('reports and renders exceptions', function (): void {
    $throwable = new RuntimeException('Boom');

    $reporter = new class implements ExceptionReporter {
        public ?Throwable $throwable = null;

        public function report(Throwable $throwable): void
        {
            $this->throwable = $throwable;
        }
    };

    $renderer = new class implements ExceptionRenderer {
        public ?Throwable $throwable = null;

        public function render(Throwable $throwable): string
        {
            $this->throwable = $throwable;

            return 'rendered exception';
        }
    };

    $rendered = new CliExceptionHandler($reporter, $renderer)->handle($throwable);

    expect($reporter->throwable)->toBe($throwable)
        ->and($renderer->throwable)->toBe($throwable)
        ->and($rendered)->toBe('rendered exception');
});
