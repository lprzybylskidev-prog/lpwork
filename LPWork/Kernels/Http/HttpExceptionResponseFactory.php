<?php

declare(strict_types=1);

namespace LPWork\Kernels\Http;

use LPWork\DebugDump\DebugDumpExceptionResponseFactory;
use LPWork\ErrorHandling\Contracts\HttpExceptionRenderer;
use LPWork\ErrorHandling\Renderers\JsonHttpExceptionRenderer;
use LPWork\Requests\HttpRequest;
use LPWork\Responses\Enums\ResponseFormat;
use LPWork\Responses\HttpResponse;
use LPWork\Validation\Exceptions\ValidationException;
use Throwable;

/**
 * Creates http exception response factory instances from framework configuration.
 */
final readonly class HttpExceptionResponseFactory
{
    /**
     * Creates a new HttpExceptionResponseFactory instance.
     */
    public function __construct(
        private HttpExceptionRenderer $htmlRenderer,
        private JsonHttpExceptionRenderer $jsonRenderer,
        private ValidationExceptionResponseFactory $validationResponses = new ValidationExceptionResponseFactory(),
        private ?DebugDumpExceptionResponseFactory $debugDumpResponses = null,
    ) {}

    /**
     * Builds or returns make.
     */
    public function make(Throwable $throwable, ResponseFormat $format, ?HttpRequest $request = null): HttpResponse
    {
        if ($this->debugDumpResponses !== null) {
            $response = $this->debugDumpResponses->make($throwable);

            if ($response !== null) {
                return $response;
            }
        }

        if ($throwable instanceof ValidationException) {
            $response = $this->validationResponses->make($throwable, $format, $request);

            if ($response !== null) {
                return $response;
            }
        }

        if ($format === ResponseFormat::Json) {
            return $this->jsonRenderer->render($throwable);
        }

        return $this->htmlRenderer->render($throwable);
    }
}
