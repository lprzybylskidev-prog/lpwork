<?php

declare(strict_types=1);

namespace LPWork\Shared\Http;

use RuntimeException;

/**
 * Represents the http client framework component.
 */
final readonly class HttpClient
{
    /**
     * Creates a new HttpClient instance.
     */
    public function __construct(
        private HttpResponseHeaderParser $headers = new HttpResponseHeaderParser(),
    ) {}

    /**
     * @param array<string, string> $headers
     */
    public function request(string $method, string $url, array $headers = [], string $body = '', int $timeoutSeconds = 30): HttpResponse
    {
        $headerLines = [];

        foreach ($headers as $name => $value) {
            $headerLines[] = "{$name}: {$value}";
        }

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headerLines),
                'content' => $body,
                'ignore_errors' => true,
                'timeout' => $timeoutSeconds,
            ],
        ]);

        $contents = @file_get_contents($url, false, $context);

        if ($contents === false) {
            throw new RuntimeException(sprintf('HTTP request [%s %s] failed.', $method, $url));
        }

        $lastResponseHeaders = http_get_last_response_headers();
        $headers = $this->headers->parse(is_array($lastResponseHeaders) ? $lastResponseHeaders : []);

        return new HttpResponse($headers->status, $headers->headers, $contents);
    }
}
