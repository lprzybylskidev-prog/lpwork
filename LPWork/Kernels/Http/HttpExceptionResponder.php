<?php

declare(strict_types=1);

namespace LPWork\Kernels\Http;

use LPWork\ErrorHandling\HttpExceptionHandler;
use LPWork\ErrorHandling\Renderers\HttpProductionExceptionRenderer;
use LPWork\Responses\HttpResponse;
use Throwable;

/**
 * Represents the http exception responder framework component.
 */
final readonly class HttpExceptionResponder
{
    /**
     * Creates a new HttpExceptionResponder instance.
     */
    public function __construct(
        private ?HttpExceptionHandler $handler,
        private HttpProductionExceptionRenderer $fallbackRenderer,
    ) {}

    /**
     * Performs the respond operation.
     */
    public function respond(Throwable $throwable): HttpResponse
    {
        if ($this->handler !== null) {
            return $this->handler->handle($throwable);
        }

        return $this->fallbackRenderer->render($throwable);
    }
}
