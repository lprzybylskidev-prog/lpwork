<?php

declare(strict_types=1);

namespace LPWork\Kernels\Http\Events;

use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;

/**
 * Represents the http request handled framework component.
 */
final readonly class HttpRequestHandled
{
    /**
     * Creates a new HttpRequestHandled instance.
     */
    public function __construct(
        public HttpRequest $request,
        public HttpResponse $response,
        public ?string $route,
        public float $durationMs,
    ) {}
}
