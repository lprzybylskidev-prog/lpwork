<?php

declare(strict_types=1);

namespace LPWork\Logging\Listeners;

use LPWork\Kernels\Http\Events\HttpRequestFailed;
use LPWork\Logging\Contracts\Logger;

/**
 * Represents the log http request failed framework component.
 */
final readonly class LogHttpRequestFailed
{
    /**
     * Creates a new LogHttpRequestFailed instance.
     */
    public function __construct(
        private Logger $logger,
    ) {}

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(HttpRequestFailed $event): void
    {
        $this->logger->error('HTTP request failed.', [
            'method' => $event->request->method(),
            'path' => $event->request->path(),
            'status' => $event->response->statusCode(),
            'route' => $event->route,
            'duration_ms' => $event->durationMs,
            'exception' => $event->throwable::class,
        ]);
    }
}
