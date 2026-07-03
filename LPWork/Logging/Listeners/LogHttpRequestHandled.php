<?php

declare(strict_types=1);

namespace LPWork\Logging\Listeners;

use LPWork\Kernels\Http\Events\HttpRequestHandled;
use LPWork\Logging\Contracts\Logger;

/**
 * Represents the log http request handled framework component.
 */
final readonly class LogHttpRequestHandled
{
    /**
     * Creates a new LogHttpRequestHandled instance.
     */
    public function __construct(
        private Logger $logger,
    ) {}

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(HttpRequestHandled $event): void
    {
        $this->logger->info('HTTP request handled.', [
            'method' => $event->request->method(),
            'path' => $event->request->path(),
            'status' => $event->response->statusCode(),
            'route' => $event->route,
            'duration_ms' => $event->durationMs,
        ]);
    }
}
