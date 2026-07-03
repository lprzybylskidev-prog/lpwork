<?php

declare(strict_types=1);

namespace LPWork\Requests;

use LPWork\Http\HttpRequestFormatResolver;
use LPWork\Requests\Contracts\Request;
use LPWork\Session\Exceptions\SessionNotAttachedException;
use LPWork\Session\Session;

/**
 * Immutable HTTP request value object used by controllers, middleware, validation, and test clients.
 */
final readonly class HttpRequest implements Request
{
    private string $method;

    private string $uri;

    private string $path;

    /**
     * @var array<string, mixed>
     */
    private array $query;

    /**
     * @var array<string, mixed>
     */
    private array $input;

    /**
     * @var array<string, mixed>
     */
    private array $cookies;

    /**
     * @var array<string, mixed>
     */
    private array $files;

    /**
     * @var array<string, string>
     */
    private array $headers;

    /**
     * @var array<string, mixed>
     */
    private array $server;

    private string $body;

    private ?Session $session;

    /**
     * @param array<string, mixed> $query Parsed query string values.
     * @param array<string, mixed> $input Parsed request body input.
     * @param array<string, mixed> $cookies Request cookie values.
     * @param array<string, mixed> $files Uploaded file payloads normalized by the request factory.
     * @param array<string, string> $headers HTTP headers; names are normalized for lookup.
     * @param array<string, mixed> $server Server metadata used for host, scheme, and client IP resolution.
     */
    public function __construct(
        string $method,
        string $uri,
        string $path,
        array $query = [],
        array $input = [],
        array $cookies = [],
        array $files = [],
        array $headers = [],
        array $server = [],
        string $body = '',
        ?Session $session = null,
    ) {
        $this->method = $method;
        $this->uri = $uri;
        $this->path = $path;
        $this->query = $query;
        $this->input = $input;
        $this->cookies = $cookies;
        $this->files = $files;
        $this->headers = self::normalizeHeaders($headers);
        $this->server = $server;
        $this->body = $body;
        $this->session = $session;
    }

    /**
     * Builds a request from explicit arrays without reading PHP superglobals.
     *
     * @param array<string, mixed> $server Server metadata such as request method, URI, host, and protocol.
     * @param array<string, mixed> $query Parsed query values.
     * @param array<string, mixed> $input Parsed input values.
     * @param array<string, mixed> $cookies Request cookie values.
     * @param array<string, mixed> $files Uploaded file values.
     */
    public static function fromArrays(
        array $server,
        array $query = [],
        array $input = [],
        array $cookies = [],
        array $files = [],
        string $body = '',
    ): self {
        return new HttpRequestFactory()->fromArrays($server, $query, $input, $cookies, $files, $body);
    }

    /**
     * Returns a copy with a session attached by session middleware or a test client.
     */
    public function withSession(Session $session): self
    {
        return $this->copy(session: $session);
    }

    /**
     * Returns a copy with replaced parsed input data.
     *
     * @param array<string, mixed> $input Parsed request input values.
     */
    public function withInput(array $input): self
    {
        return $this->copy(input: $input);
    }

    /**
     * Builds a request from PHP superglobals at the HTTP boundary.
     */
    public static function fromGlobals(): self
    {
        return new HttpRequestFactory()->fromGlobals();
    }

    /**
     * Returns the normalized HTTP method.
     */
    public function method(): string
    {
        return $this->method;
    }

    /**
     * Returns the request URI including the query string when present.
     */
    public function uri(): string
    {
        return $this->uri;
    }

    /**
     * Returns the normalized request path without the query string.
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * Returns all parsed query string values.
     *
     * @return array<string, mixed>
     */
    public function query(): array
    {
        return $this->query;
    }

    /**
     * Returns a query value or the supplied default when the key is missing.
     */
    public function queryValue(string $key, mixed $default = null): mixed
    {
        return $this->queryAccessor()->value($key, $default);
    }

    /**
     * Returns a query value coerced to string.
     */
    public function queryString(string $key, string $default = ''): string
    {
        return $this->queryAccessor()->string($key, $default);
    }

    /**
     * Returns a query value coerced to integer.
     */
    public function queryInteger(string $key, int $default = 0): int
    {
        return $this->queryAccessor()->integer($key, $default);
    }

    /**
     * Returns a query value coerced to float.
     */
    public function queryFloat(string $key, float $default = 0.0): float
    {
        return $this->queryAccessor()->float($key, $default);
    }

    /**
     * Returns a query value coerced to boolean.
     */
    public function queryBoolean(string $key, bool $default = false): bool
    {
        return $this->queryAccessor()->boolean($key, $default);
    }

    /**
     * Returns a query value as an array.
     *
     * @param array<array-key, mixed> $default Default array returned when the key is missing or not an array.
     *
     * @return array<array-key, mixed>
     */
    public function queryArray(string $key, array $default = []): array
    {
        return $this->queryAccessor()->array($key, $default);
    }

    /**
     * Reports whether the query contains the given key.
     */
    public function queryHas(string $key): bool
    {
        return $this->queryAccessor()->has($key);
    }

    /**
     * Reports whether the query key exists and is not an empty string or empty array.
     */
    public function queryFilled(string $key): bool
    {
        return $this->queryAccessor()->filled($key);
    }

    /**
     * Reports whether the query key is absent.
     */
    public function queryMissing(string $key): bool
    {
        return $this->queryAccessor()->missing($key);
    }

    /**
     * Returns only the selected query values.
     *
     * @param list<string> $keys Query keys to include.
     *
     * @return array<string, mixed>
     */
    public function queryOnly(array $keys): array
    {
        return $this->queryAccessor()->only($keys);
    }

    /**
     * Returns query values except the selected keys.
     *
     * @param list<string> $keys Query keys to omit.
     *
     * @return array<string, mixed>
     */
    public function queryExcept(array $keys): array
    {
        return $this->queryAccessor()->except($keys);
    }

    /**
     * Returns all parsed request input values.
     *
     * @return array<string, mixed>
     */
    public function input(): array
    {
        return $this->input;
    }

    /**
     * Returns an input value or the supplied default when the key is missing.
     */
    public function inputValue(string $key, mixed $default = null): mixed
    {
        return $this->inputAccessor()->value($key, $default);
    }

    /**
     * Returns an input value coerced to string.
     */
    public function string(string $key, string $default = ''): string
    {
        return $this->inputAccessor()->string($key, $default);
    }

    /**
     * Returns an input value coerced to integer.
     */
    public function integer(string $key, int $default = 0): int
    {
        return $this->inputAccessor()->integer($key, $default);
    }

    /**
     * Returns an input value coerced to float.
     */
    public function float(string $key, float $default = 0.0): float
    {
        return $this->inputAccessor()->float($key, $default);
    }

    /**
     * Returns an input value coerced to boolean.
     */
    public function boolean(string $key, bool $default = false): bool
    {
        return $this->inputAccessor()->boolean($key, $default);
    }

    /**
     * Returns an input value as an array.
     *
     * @param array<array-key, mixed> $default Default array returned when the key is missing or not an array.
     *
     * @return array<array-key, mixed>
     */
    public function array(string $key, array $default = []): array
    {
        return $this->inputAccessor()->array($key, $default);
    }

    /**
     * Reports whether parsed input contains the given key.
     */
    public function has(string $key): bool
    {
        return $this->inputAccessor()->has($key);
    }

    /**
     * Reports whether the input key exists and is not an empty string or empty array.
     */
    public function filled(string $key): bool
    {
        return $this->inputAccessor()->filled($key);
    }

    /**
     * Reports whether the input key is absent.
     */
    public function missing(string $key): bool
    {
        return $this->inputAccessor()->missing($key);
    }

    /**
     * Returns only the selected input values.
     *
     * @param list<string> $keys Input keys to include.
     *
     * @return array<string, mixed>
     */
    public function only(array $keys): array
    {
        return $this->inputAccessor()->only($keys);
    }

    /**
     * Returns input values except the selected keys.
     *
     * @param list<string> $keys Input keys to omit.
     *
     * @return array<string, mixed>
     */
    public function except(array $keys): array
    {
        return $this->inputAccessor()->except($keys);
    }

    /**
     * Returns all request cookies.
     *
     * @return array<string, mixed>
     */
    public function cookies(): array
    {
        return $this->cookies;
    }

    /**
     * Returns a cookie value or the supplied default when the cookie is missing.
     */
    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * Returns all uploaded file payloads normalized by the request factory.
     *
     * @return array<string, mixed>
     */
    public function files(): array
    {
        return $this->files;
    }

    /**
     * Returns one uploaded file when it was normalized as an UploadedFile instance.
     */
    public function file(string $key): ?UploadedFile
    {
        $file = new RequestDataAccessor($this->files)->value($key);

        return $file instanceof UploadedFile ? $file : null;
    }

    /**
     * Returns normalized request headers keyed by lowercase header name.
     *
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Returns a header value using case-insensitive header name lookup.
     */
    public function header(string $name, ?string $default = null): ?string
    {
        return $this->headers[self::normalizeHeaderName($name)] ?? $default;
    }

    /**
     * Returns the response or message body.
     */
    public function body(): string
    {
        return $this->body;
    }

    /**
     * Returns the normalized request host without any port suffix.
     */
    public function host(): string
    {
        $host = $this->header('Host');

        if ($host === null || $host === '') {
            $host = self::stringValue($this->server['SERVER_NAME'] ?? '');
        }

        return self::normalizeHost($host);
    }

    /**
     * Returns the request scheme inferred from server metadata.
     */
    public function scheme(): string
    {
        $https = strtolower(self::stringValue($this->server['HTTPS'] ?? ''));

        if ($https === 'on' || $https === '1') {
            return 'https';
        }

        $scheme = strtolower(self::stringValue($this->server['REQUEST_SCHEME'] ?? ''));

        if ($scheme === 'https') {
            return 'https';
        }

        if (self::stringValue($this->server['SERVER_PORT'] ?? '') === '443') {
            return 'https';
        }

        return 'http';
    }

    /**
     * Returns the direct client IP from REMOTE_ADDR.
     */
    public function clientIp(): string
    {
        return self::stringValue($this->server['REMOTE_ADDR'] ?? '');
    }

    /**
     * Alias for clientIp().
     */
    public function ip(): string
    {
        return $this->clientIp();
    }

    /**
     * Reports whether the request method matches the supplied method name.
     */
    public function isMethod(string $method): bool
    {
        return $this->method === self::normalizeMethod($method);
    }

    /**
     * Reports whether the request content type indicates a JSON body.
     */
    public function isJson(): bool
    {
        return new HttpRequestFormatResolver()->hasJsonBody($this);
    }

    /**
     * Reports whether response negotiation prefers JSON.
     */
    public function expectsJson(): bool
    {
        return new HttpRequestFormatResolver()->expectsJson($this);
    }

    /**
     * Returns the absolute URL without the query string when host data is available.
     */
    public function url(): string
    {
        $host = $this->host();

        if ($host === '') {
            return $this->path;
        }

        return $this->scheme() . '://' . $host . $this->path;
    }

    /**
     * Returns the absolute URL including the query string when host data is available.
     */
    public function fullUrl(): string
    {
        $host = $this->host();

        if ($host === '') {
            return $this->uri;
        }

        return $this->scheme() . '://' . $host . $this->uri;
    }

    /**
     * Returns the first X-Forwarded-Host value normalized for trusted proxy handling.
     */
    public function forwardedHost(): ?string
    {
        $host = $this->firstHeaderValue('X-Forwarded-Host');

        return $host === null ? null : self::normalizeHost($host);
    }

    /**
     * Returns the first trusted forwarded scheme when it is http or https.
     */
    public function forwardedScheme(): ?string
    {
        $scheme = strtolower((string) $this->firstHeaderValue('X-Forwarded-Proto'));

        return match ($scheme) {
            'http', 'https' => $scheme,
            default => null,
        };
    }

    /**
     * Returns the numeric Content-Length header when it is present and valid.
     */
    public function contentLength(): ?int
    {
        $contentLength = $this->header('Content-Length');

        if ($contentLength === null || preg_match('/^\d+$/', $contentLength) !== 1) {
            return null;
        }

        return (int) $contentLength;
    }

    /**
     * Returns the attached session or fails when session middleware has not attached one.
     */
    public function session(): Session
    {
        return $this->session ?? throw new SessionNotAttachedException();
    }

    private function queryAccessor(): RequestDataAccessor
    {
        return new RequestDataAccessor($this->query);
    }

    private function inputAccessor(): RequestDataAccessor
    {
        return new RequestDataAccessor($this->input);
    }

    /**
     * @param array<string, string> $headers
     *
     * @return array<string, string>
     */
    private static function normalizeHeaders(array $headers): array
    {
        $normalized = [];

        foreach ($headers as $name => $value) {
            $normalized[self::normalizeHeaderName($name)] = $value;
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed>|null $input
     */
    private function copy(?array $input = null, ?Session $session = null): self
    {
        return new self(
            method: $this->method,
            uri: $this->uri,
            path: $this->path,
            query: $this->query,
            input: $input ?? $this->input,
            cookies: $this->cookies,
            files: $this->files,
            headers: $this->headers,
            server: $this->server,
            body: $this->body,
            session: $session ?? $this->session,
        );
    }

    private static function normalizeHeaderName(string $name): string
    {
        return str_replace(' ', '-', ucwords(strtolower(str_replace(['-', '_'], ' ', $name))));
    }

    private static function stringValue(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    private static function normalizeMethod(string $method): string
    {
        $method = strtoupper(trim($method));

        return $method === '' ? 'GET' : $method;
    }

    private function firstHeaderValue(string $name): ?string
    {
        $value = $this->header($name);

        if ($value === null || $value === '') {
            return null;
        }

        $parts = explode(',', $value);
        $first = trim($parts[0]);

        return $first === '' ? null : $first;
    }

    private static function normalizeHost(string $host): string
    {
        $host = strtolower(trim($host));

        if ($host === '') {
            return '';
        }

        if (str_starts_with($host, '[')) {
            $end = strpos($host, ']');

            return $end === false ? trim($host, '[]') : substr($host, 1, $end - 1);
        }

        $host = explode(':', $host, 2)[0];

        return rtrim($host, '.');
    }
}
