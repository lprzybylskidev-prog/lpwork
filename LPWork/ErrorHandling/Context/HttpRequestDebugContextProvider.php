<?php

declare(strict_types=1);

namespace LPWork\ErrorHandling\Context;

use LPWork\ErrorHandling\Contracts\HttpDebugContextProvider;
use LPWork\ErrorHandling\HttpDebugContext;

/**
 * Registers http request debug context provider services with the framework container.
 */
final readonly class HttpRequestDebugContextProvider implements HttpDebugContextProvider
{
    /**
     * @return array<string, mixed>
     */
    public function context(HttpDebugContext $context): array
    {
        $request = $context->request();

        if ($request === null) {
            return [];
        }

        $response = $context->response();

        return [
            'Request' => [
                'Method' => $request->method(),
                'URL' => $request->fullUrl(),
                'Path' => $request->path(),
                'Host' => $request->host(),
                'Scheme' => $request->scheme(),
                'Client IP' => $request->ip(),
                'Expects JSON' => $request->expectsJson(),
                'Has JSON body' => $request->isJson(),
                'Content length' => $request->contentLength(),
                'Body size bytes' => strlen($request->body()),
            ],
            'Response' => $response === null ? [
                'Captured' => false,
            ] : [
                'Captured' => true,
                'Status' => $response->statusCode(),
                'Content type' => $response->header('Content-Type'),
                'Content length header' => $response->header('Content-Length'),
                'Body size bytes' => strlen($response->body()),
                'Cookie count' => count($response->cookies()),
                'Streamed' => $response->streamCallback() !== null,
            ],
            'Request data' => [
                'Query' => $request->query(),
                'Input' => $request->input(),
                'Cookies' => $request->cookies(),
                'Files' => $request->files(),
                'Headers' => $request->headers(),
                'Body' => $request->body(),
            ],
        ];
    }
}
