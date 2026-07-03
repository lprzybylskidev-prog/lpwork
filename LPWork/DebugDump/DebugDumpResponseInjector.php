<?php

declare(strict_types=1);

namespace LPWork\DebugDump;

use LPWork\Responses\HttpResponse;

/**
 * Represents the debug dump response injector framework component.
 */
final readonly class DebugDumpResponseInjector
{
    /**
     * Creates a new DebugDumpResponseInjector instance.
     */
    public function __construct(
        private DebugDumpStore $store,
        private DebugDumpRenderer $renderer,
        private bool $enabled,
    ) {}

    /**
     * Performs the inject operation.
     */
    public function inject(HttpResponse $response): HttpResponse
    {
        $records = $this->store->flush();

        if (!$this->enabled || $records === [] || !$this->canInject($response)) {
            return $response;
        }

        $overlay = $this->renderer->overlay($records);

        if ($overlay === '') {
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

        $body = substr($body, 0, $position) . $overlay . substr($body, $position);

        return $response
            ->withBody($body)
            ->withHeader('Content-Length', (string) strlen($body));
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
