<?php

declare(strict_types=1);

namespace LPWork\ErrorHandling;

use LPWork\ErrorHandling\Contracts\ExceptionReporter;
use LPWork\ErrorHandling\Contracts\HttpExceptionRenderer;
use LPWork\Responses\HttpResponse;
use Throwable;

/**
 * Represents the http exception handler framework component.
 */
final readonly class HttpExceptionHandler
{
    /**
     * Creates a new HttpExceptionHandler instance.
     */
    public function __construct(
        private ExceptionReporter $reporter,
        private HttpExceptionRenderer $renderer,
    ) {}

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Throwable $throwable): HttpResponse
    {
        $this->reporter->report($throwable);

        return $this->renderer->render($throwable);
    }
}
