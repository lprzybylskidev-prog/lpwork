<?php

declare(strict_types=1);

namespace Tests\support\testing\Http;

use JsonException;
use LPWork\Requests\HttpRequest;
use Tests\support\exceptions\TestSupportException;

final class HttpRequestBuilder
{
    /**
     * @var array<string, mixed>
     */
    private array $query = [];

    /**
     * @var array<string, mixed>
     */
    private array $input = [];

    /**
     * @var array<string, mixed>
     */
    private array $cookies = [];

    /**
     * @var array<string, mixed>
     */
    private array $files = [];

    /**
     * @var array<string, string>
     */
    private array $headers = [];

    /**
     * @var array<string, mixed>
     */
    private array $server = [];

    private string $body = '';

    private function __construct(
        private readonly string $method,
        private readonly string $uri,
    ) {}

    public static function request(string $method, string $uri): self
    {
        return new self($method, $uri);
    }

    /**
     * @param array<string, mixed> $query
     */
    public function withQuery(array $query): self
    {
        $this->query = $query;

        return $this;
    }

    /**
     * @param array<string, mixed> $input
     */
    public function withInput(array $input): self
    {
        $this->input = $input;

        return $this;
    }

    /**
     * @param array<string, mixed> $cookies
     */
    public function withCookies(array $cookies): self
    {
        $this->cookies = $cookies;

        return $this;
    }

    /**
     * @param array<string, mixed> $files
     */
    public function withFiles(array $files): self
    {
        $this->files = $files;

        return $this;
    }

    /**
     * @param array<string, string> $headers
     */
    public function withHeaders(array $headers): self
    {
        $this->headers = [
            ...$this->headers,
            ...$headers,
        ];

        return $this;
    }

    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * @param array<string, mixed> $server
     */
    public function withServer(array $server): self
    {
        $this->server = $server;

        return $this;
    }

    public function withBody(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @param array<string, mixed>|list<mixed> $data
     */
    public function withJsonBody(array $data): self
    {
        try {
            $this->body = json_encode($data, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw TestSupportException::jsonCouldNotBeEncoded($exception->getMessage());
        }

        $this->headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            ...$this->headers,
        ];

        return $this;
    }

    public function build(): HttpRequest
    {
        return HttpRequest::fromArrays(
            server: $this->server(),
            query: $this->query(),
            input: $this->input,
            cookies: $this->cookies,
            files: $this->files,
            body: $this->body,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function server(): array
    {
        return [
            'SERVER_NAME' => 'localhost',
            'HTTP_HOST' => 'localhost',
            'SERVER_PORT' => '80',
            'REQUEST_SCHEME' => 'http',
            'REMOTE_ADDR' => '127.0.0.1',
            ...$this->server,
            ...$this->headerServerValues(),
            'REQUEST_METHOD' => strtoupper($this->method),
            'REQUEST_URI' => $this->requestUri(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function query(): array
    {
        return [
            ...$this->queryFromUri(),
            ...$this->query,
        ];
    }

    private function requestUri(): string
    {
        $path = parse_url($this->uri, PHP_URL_PATH);
        $path = is_string($path) && $path !== '' ? $path : '/';
        $query = http_build_query($this->query(), '', '&', PHP_QUERY_RFC3986);

        if ($query === '') {
            return $path;
        }

        return $path . '?' . $query;
    }

    /**
     * @return array<string, mixed>
     */
    private function queryFromUri(): array
    {
        $rawQuery = parse_url($this->uri, PHP_URL_QUERY);

        if (!is_string($rawQuery) || $rawQuery === '') {
            return [];
        }

        parse_str($rawQuery, $parsedQuery);

        return $this->stringKeyedArray($parsedQuery);
    }

    /**
     * @param array<int|string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private function stringKeyedArray(array $values): array
    {
        $normalized = [];

        foreach ($values as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /**
     * @return array<string, string>
     */
    private function headerServerValues(): array
    {
        $server = [];

        foreach ($this->headers as $name => $value) {
            $key = strtoupper(str_replace('-', '_', $name));

            if ($key === 'CONTENT_TYPE' || $key === 'CONTENT_LENGTH') {
                $server[$key] = $value;

                continue;
            }

            $server['HTTP_' . $key] = $value;
        }

        if ($this->body !== '' && !array_key_exists('CONTENT_LENGTH', $server)) {
            $server['CONTENT_LENGTH'] = (string) strlen($this->body);
        }

        return $server;
    }
}
