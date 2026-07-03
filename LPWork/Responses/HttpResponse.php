<?php

declare(strict_types=1);

namespace LPWork\Responses;

use Closure;
use JsonException;
use LPWork\Http\Cookie;
use LPWork\Responses\Contracts\Response;
use LPWork\Responses\Exceptions\FileResponseReadException;
use LPWork\Responses\Exceptions\InvalidRedirectStatusException;
use LPWork\Responses\Exceptions\JsonResponseEncodingException;

/**
 * Immutable HTTP response value object representing status, headers, cookies, body, and optional streaming output.
 */
final readonly class HttpResponse implements Response
{
    private string $body;

    private int $statusCode;

    /**
     * @var array<string, string>
     */
    private array $headers;

    /**
     * @var list<Cookie>
     */
    private array $cookies;

    /**
     * @var (Closure(resource): void)|null
     */
    private ?Closure $stream;

    /**
     * @param array<string, string> $headers
     * @param list<Cookie> $cookies
     * @param (Closure(resource): void)|null $stream
     */
    public function __construct(
        string $body = '',
        int $statusCode = 200,
        array $headers = [],
        array $cookies = [],
        ?Closure $stream = null,
    ) {
        $this->body = $body;
        $this->statusCode = $statusCode;
        $this->headers = self::normalizeHeaders($headers);
        $this->cookies = $cookies;
        $this->stream = $stream;
    }

    /**
     * Creates a plain text response with a UTF-8 text content type.
     *
     * @param array<string, string> $headers Additional or overriding response headers.
     */
    public static function text(string $body, int $statusCode = 200, array $headers = []): self
    {
        return new self(
            body: $body,
            statusCode: $statusCode,
            headers: ['Content-Type' => 'text/plain; charset=UTF-8', ...$headers],
        );
    }

    /**
     * Creates an HTML response with a UTF-8 HTML content type.
     *
     * @param array<string, string> $headers Additional or overriding response headers.
     */
    public static function html(string $body, int $statusCode = 200, array $headers = []): self
    {
        return new self(
            body: $body,
            statusCode: $statusCode,
            headers: ['Content-Type' => 'text/html; charset=UTF-8', ...$headers],
        );
    }

    /**
     * Creates a JSON response and fails through a domain exception when encoding is not possible.
     *
     * @param array<string, mixed>|list<mixed> $data JSON-serializable payload.
     * @param array<string, string> $headers Additional or overriding response headers.
     */
    public static function json(array $data, int $statusCode = 200, array $headers = []): self
    {
        try {
            $body = json_encode($data, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw JsonResponseEncodingException::forPrevious($exception);
        }

        return new self(
            body: $body,
            statusCode: $statusCode,
            headers: ['Content-Type' => 'application/json; charset=UTF-8', ...$headers],
        );
    }

    /**
     * Creates a redirect response for a supported 3xx redirect status.
     *
     * @param array<string, string> $headers Additional or overriding response headers.
     */
    public static function redirect(string $location, int $statusCode = 302, array $headers = []): self
    {
        if (!in_array($statusCode, [301, 302, 303, 307, 308], true)) {
            throw new InvalidRedirectStatusException($statusCode);
        }

        return new self('', $statusCode, ['Location' => $location, ...$headers]);
    }

    /**
     * Creates a response with status 204 and no body.
     *
     * @param array<string, string> $headers Additional response headers.
     */
    public static function noContent(array $headers = []): self
    {
        return new self('', 204, $headers);
    }

    /**
     * Creates a response for newly created resources and sets the `Location` header.
     *
     * @param array<string, string> $headers Additional or overriding response headers.
     */
    public static function created(string $location, string $body = '', array $headers = []): self
    {
        return new self($body, 201, ['Location' => $location, ...$headers]);
    }

    /**
     * Creates a streaming response; emitters invoke the callback with the output stream.
     *
     * @param Closure(resource): void $stream Writes response bytes to the provided stream resource.
     * @param array<string, string> $headers Additional response headers.
     */
    public static function stream(Closure $stream, int $statusCode = 200, array $headers = []): self
    {
        return new self(statusCode: $statusCode, headers: $headers, stream: $stream);
    }

    /**
     * Creates a streamed binary file response for a readable local file path.
     *
     * @param array<string, string> $headers Additional or overriding response headers.
     */
    public static function file(string $path, array $headers = []): self
    {
        $size = filesize($path);

        if ($size === false) {
            throw new FileResponseReadException($path);
        }

        return self::stream(self::fileStreamer($path), headers: [
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => (string) $size,
            ...$headers,
        ]);
    }

    /**
     * Creates a streamed file download response with a `Content-Disposition` filename.
     *
     * @param array<string, string> $headers Additional or overriding response headers.
     */
    public static function download(string $path, ?string $name = null, array $headers = []): self
    {
        $name ??= basename($path);

        return self::file($path, [
            'Content-Disposition' => 'attachment; filename="' . addcslashes($name, "\\\"") . '"',
            ...$headers,
        ]);
    }

    /**
     * Returns the buffered response body.
     */
    public function body(): string
    {
        return $this->body;
    }

    /**
     * Returns the HTTP status code.
     */
    public function statusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Returns normalized response headers keyed by lowercase header name.
     *
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Returns a response header value using case-insensitive header name lookup.
     */
    public function header(string $name, ?string $default = null): ?string
    {
        return $this->headers[self::normalizeHeaderName($name)] ?? $default;
    }

    /**
     * Returns cookies queued for the response emitter.
     *
     * @return list<Cookie>
     */
    public function cookies(): array
    {
        return $this->cookies;
    }

    /**
     * Returns the streaming callback for streamed responses.
     *
     * @return (Closure(resource): void)|null
     */
    public function streamCallback(): ?Closure
    {
        return $this->stream;
    }

    /**
     * Returns a copy with a different HTTP status code.
     */
    public function withStatus(int $statusCode): self
    {
        return $this->copy(statusCode: $statusCode);
    }

    /**
     * Returns a copy with a different string body.
     */
    public function withBody(string $body): self
    {
        return $this->copy(body: $body);
    }

    /**
     * Returns a copy with a normalized header value set or replaced.
     */
    public function withHeader(string $name, string $value): self
    {
        return $this->copy(headers: [
            ...$this->headers,
            self::normalizeHeaderName($name) => $value,
        ]);
    }

    /**
     * Returns a copy with a normalized header removed.
     */
    public function withoutHeader(string $name): self
    {
        $headers = $this->headers;
        unset($headers[self::normalizeHeaderName($name)]);

        return $this->copy(headers: $headers);
    }

    /**
     * Returns a copy with the cookie appended, replacing any cookie with the same name.
     */
    public function withCookie(Cookie $cookie): self
    {
        return $this->copy(cookies: [...$this->cookiesWithout($cookie->name()), $cookie]);
    }

    /**
     * Returns a copy carrying an expired cookie instruction for the given name/path/domain.
     */
    public function withoutCookie(string $name, string $path = '/', string $domain = ''): self
    {
        return $this->withCookie(Cookie::forget($name, $path, $domain));
    }

    /**
     * @param array<string, string>|null $headers
     * @param list<Cookie>|null $cookies
     * @param (Closure(resource): void)|null $stream
     */
    private function copy(
        ?string $body = null,
        ?int $statusCode = null,
        ?array $headers = null,
        ?array $cookies = null,
        ?Closure $stream = null,
    ): self {
        return new self(
            body: $body ?? $this->body,
            statusCode: $statusCode ?? $this->statusCode,
            headers: $headers ?? $this->headers,
            cookies: $cookies ?? $this->cookies,
            stream: $stream ?? $this->stream,
        );
    }

    /**
     * @return list<Cookie>
     */
    private function cookiesWithout(string $name): array
    {
        return array_values(array_filter(
            $this->cookies,
            static fn(Cookie $cookie): bool => $cookie->name() !== $name,
        ));
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

    private static function normalizeHeaderName(string $name): string
    {
        return str_replace(' ', '-', ucwords(strtolower(str_replace(['-', '_'], ' ', $name))));
    }

    /**
     * @return Closure(resource): void
     */
    private static function fileStreamer(string $path): Closure
    {
        return static function (mixed $output) use ($path): void {
            if (!is_resource($output)) {
                throw new FileResponseReadException($path);
            }

            $file = fopen($path, 'rb');

            if ($file === false) {
                throw new FileResponseReadException($path);
            }

            try {
                while (!feof($file)) {
                    $chunk = fread($file, 8192);

                    if ($chunk === false || fwrite($output, $chunk) === false) {
                        throw new FileResponseReadException($path);
                    }
                }
            } finally {
                fclose($file);
            }
        };
    }
}
