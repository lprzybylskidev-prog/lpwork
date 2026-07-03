<?php

declare(strict_types=1);

namespace LPWork\DebugBar;

use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;

/**
 * Handles debug bar controller HTTP requests.
 */
final readonly class DebugBarController
{
    /**
     * Creates a new DebugBarController instance.
     */
    public function __construct(
        private DebugBarRequestStore $store,
        private bool $enabled = true,
    ) {}

    /**
     * Performs the requests operation.
     */
    public function requests(HttpRequest $request): HttpResponse
    {
        if (!$this->enabled) {
            return HttpResponse::json(['error' => 'Debugbar is disabled.'], statusCode: 404);
        }

        $session = $request->queryString('session');

        if ($session === '') {
            return HttpResponse::json(['requests' => []]);
        }

        return HttpResponse::json([
            'requests' => array_map(
                static fn(array $record): array => [
                    'id' => $record['id'] ?? '',
                    'label' => $record['label'] ?? 'request',
                    'summary' => $record['summary'] ?? '',
                    'status' => $record['status'] ?? 'n/a',
                    'duration' => $record['duration'] ?? 'n/a',
                    'recordedAt' => $record['recordedAt'] ?? 0,
                ],
                $this->store->list($session),
            ),
        ]);
    }

    /**
     * Performs the request operation.
     */
    public function request(HttpRequest $request): HttpResponse
    {
        if (!$this->enabled) {
            return HttpResponse::json(['error' => 'Debugbar is disabled.'], statusCode: 404);
        }

        $session = $request->queryString('session');
        $id = $request->queryString('id');
        $record = $session === '' || $id === '' ? null : $this->store->get($session, $id);

        if ($record === null) {
            return HttpResponse::json(['error' => 'Debugbar request snapshot was not found.'], statusCode: 404);
        }

        return HttpResponse::json(['request' => $record]);
    }
}
