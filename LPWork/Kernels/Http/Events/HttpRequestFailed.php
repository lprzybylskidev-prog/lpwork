<?php

declare(strict_types=1);

namespace LPWork\Kernels\Http\Events;

use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;
use Throwable;

/**
 * Represents the http request failed framework component.
 */
final readonly class HttpRequestFailed
{
    /**
     * Creates a new HttpRequestFailed instance.
     */
    public function __construct(
        public HttpRequest $request,
        public HttpResponse $response,
        public ?string $route,
        public float $durationMs,
        public Throwable $throwable,
    ) {}
}
