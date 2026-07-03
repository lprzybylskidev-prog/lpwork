<?php

declare(strict_types=1);

namespace LPWork\DebugBar;

use LPWork\Observability\DiagnosticsSnapshotFactory;
use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;

/**
 * Represents the debug bar response injector framework component.
 */
final readonly class DebugBarResponseInjector
{
    /**
     * Creates a new DebugBarResponseInjector instance.
     */
    public function __construct(
        private DiagnosticsSnapshotFactory $snapshots,
        private DebugBarRenderer $renderer,
        private DebugBarRequestStore $store,
        private bool $enabled,
    ) {}

    /**
     * Performs the inject operation.
     */
    public function inject(HttpRequest $request, HttpResponse $response): HttpResponse
    {
        if (!$this->enabled || $this->isDebugBarRequest($request)) {
            return $response;
        }

        $session = $this->debugSession($request);
        $id = $this->debugRequestId();
        $payload = $this->renderer->payload($this->snapshots->make(), $id, $session);
        $this->store->put($session, $id, $payload);

        $response = $response
            ->withHeader('X-LPWork-Debug-Id', $id)
            ->withHeader('X-LPWork-Debug-Session', $session);

        if (!$this->canInject($response)) {
            return $response;
        }

        $bar = $this->renderer->renderPayload($payload);

        if ($bar === '') {
            return $response;
        }

        $body = $response->body();
        $position = $this->bodyOpeningEnd($body);

        if ($position === false) {
            $position = strripos($body, '</body>');

            if ($position === false) {
                return $response;
            }
        }

        $body = substr($body, 0, $position) . $bar . substr($body, $position);

        return $response
            ->withBody($body)
            ->withHeader('Content-Length', (string) strlen($body));
    }

    private function debugSession(HttpRequest $request): string
    {
        $session = $request->header('X-LPWork-Debug-Session');

        if ($session !== null && preg_match('/^[a-zA-Z0-9_-]{12,80}$/', $session) === 1) {
            return $session;
        }

        return bin2hex(random_bytes(16));
    }

    private function debugRequestId(): string
    {
        return bin2hex(random_bytes(12));
    }

    private function isDebugBarRequest(HttpRequest $request): bool
    {
        return str_starts_with($request->path(), '/__lpwork/debugbar');
    }

    private function bodyOpeningEnd(string $body): int|false
    {
        $bodyStart = stripos($body, '<body');

        if ($bodyStart === false) {
            return false;
        }

        $bodyEnd = strpos($body, '>', $bodyStart);

        return $bodyEnd === false ? false : $bodyEnd + 1;
    }

    private function canInject(HttpResponse $response): bool
    {
        if ($response->streamCallback() !== null) {
            return false;
        }

        if ($response->statusCode() < 200 || $response->statusCode() >= 300) {
            return false;
        }

        if ($response->header('Location') !== null) {
            return false;
        }

        $contentType = strtolower($response->header('Content-Type') ?? '');

        return str_contains($contentType, 'text/html');
    }
}
