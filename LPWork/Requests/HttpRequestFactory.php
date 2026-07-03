<?php

declare(strict_types=1);

namespace LPWork\Requests;

/**
 * Creates HTTP request objects from explicit arrays or PHP superglobals.
 */
final readonly class HttpRequestFactory
{
    /**
     * @param array<string, mixed> $server
     * @param array<string, mixed> $query
     * @param array<string, mixed> $input
     * @param array<string, mixed> $cookies
     * @param array<string, mixed> $files
     */
    public function fromArrays(
        array $server,
        array $query = [],
        array $input = [],
        array $cookies = [],
        array $files = [],
        string $body = '',
    ): HttpRequest {
        $uri = $this->stringValue($server['REQUEST_URI'] ?? '/');

        return new HttpRequest(
            method: $this->normalizeMethod($this->stringValue($server['REQUEST_METHOD'] ?? 'GET')),
            uri: $uri,
            path: $this->pathFromUri($uri),
            query: $query,
            input: $input,
            cookies: $cookies,
            files: new UploadedFilesParser()->parse($files),
            headers: $this->headersFromServer($server),
            server: $server,
            body: $body,
        );
    }

    /**
     * Builds a request from PHP superglobals at the HTTP boundary.
     */
    public function fromGlobals(): HttpRequest
    {
        $server = $this->stringKeyMap($_SERVER);
        $query = $this->stringKeyMap($_GET);
        $input = $this->stringKeyMap($_POST);
        $cookies = $this->stringKeyMap($_COOKIE);
        $files = $this->stringKeyMap($_FILES);

        return $this->fromArrays(
            server: $server,
            query: $query,
            input: $input,
            cookies: $cookies,
            files: $files,
            body: $this->requestBody(),
        );
    }

    /**
     * @param array<string, mixed> $server
     *
     * @return array<string, string>
     */
    private function headersFromServer(array $server): array
    {
        $headers = [];

        foreach ($server as $key => $value) {
            if (!is_scalar($value)) {
                continue;
            }

            if (str_starts_with($key, 'HTTP_')) {
                $headers[$this->normalizeHeaderName(substr($key, 5))] = (string) $value;

                continue;
            }

            if ($key === 'CONTENT_TYPE' || $key === 'CONTENT_LENGTH') {
                $headers[$this->normalizeHeaderName($key)] = (string) $value;
            }
        }

        return $headers;
    }

    /**
     * Normalizes boundary arrays to string-keyed maps.
     *
     * @param array<array-key, mixed> $values
     *
     * @return array<string, mixed>
     */
    private function stringKeyMap(array $values): array
    {
        $map = [];

        foreach ($values as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            $map[$key] = $value;
        }

        return $map;
    }

    private function normalizeHeaderName(string $name): string
    {
        return str_replace(' ', '-', ucwords(strtolower(str_replace(['-', '_'], ' ', $name))));
    }

    private function normalizeMethod(string $method): string
    {
        $method = strtoupper(trim($method));

        return $method === '' ? 'GET' : $method;
    }

    private function pathFromUri(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH);

        return is_string($path) && $path !== '' ? $path : '/';
    }

    private function stringValue(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    private function requestBody(): string
    {
        $body = file_get_contents('php://input');

        return is_string($body) ? $body : '';
    }
}
