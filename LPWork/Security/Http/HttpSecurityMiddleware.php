<?php

declare(strict_types=1);

namespace LPWork\Security\Http;

use Closure;
use LPWork\Events\EventDispatcher;
use LPWork\Http\Exceptions\BadRequestException;
use LPWork\Http\Exceptions\ForbiddenException;
use LPWork\Http\Exceptions\PayloadTooLargeException;
use LPWork\Middleware\Contracts\Middleware;
use LPWork\Requests\HttpRequest;
use LPWork\Requests\UploadedFile;
use LPWork\Responses\HttpResponse;
use LPWork\Security\Events\HttpSecurityDenied;
use LPWork\Security\SecurityConfig;

/**
 * Applies http security middleware middleware behavior.
 */
final readonly class HttpSecurityMiddleware implements Middleware
{
    /**
     * Creates a new HttpSecurityMiddleware instance.
     */
    public function __construct(
        private SecurityConfig $security,
        private ?EventDispatcher $events = null,
    ) {}

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(HttpRequest $request, Closure $next): HttpResponse
    {
        $this->validateHost($request);
        $this->validateHttps($request);
        $this->validateBodySize($request);
        $this->validateUploadSize($request->files());

        return $this->withSecurityHeaders($next($request));
    }

    private function validateHost(HttpRequest $request): void
    {
        $trustedHosts = $this->security->http()->trustedHosts();

        if ($trustedHosts === []) {
            return;
        }

        if (!new TrustedHostMatcher($trustedHosts)->matches($this->effectiveHost($request))) {
            $this->deny('untrusted_host', 'HTTP security denied untrusted host.', [
                'host' => $this->effectiveHost($request),
                'path' => $request->path(),
                'client_ip' => $request->clientIp(),
            ], $request);

            throw new BadRequestException('Untrusted HTTP host.');
        }
    }

    private function validateHttps(HttpRequest $request): void
    {
        if (!$this->security->http()->enforcesHttps()) {
            return;
        }

        if ($this->security->allowsLocalFlows() && $this->isLocalHost($this->effectiveHost($request))) {
            return;
        }

        if ($this->effectiveScheme($request) !== 'https') {
            $this->deny('non_https', 'HTTP security denied non-HTTPS request.', [
                'scheme' => $this->effectiveScheme($request),
                'host' => $this->effectiveHost($request),
                'path' => $request->path(),
                'client_ip' => $request->clientIp(),
            ], $request);

            throw new ForbiddenException('HTTPS is required.');
        }
    }

    private function validateBodySize(HttpRequest $request): void
    {
        $max = $this->security->http()->maxRequestBodyBytes();
        $contentLength = $request->contentLength();

        if ($max > 0 && $contentLength !== null && $contentLength > $max) {
            $this->deny('oversized_request_body', 'HTTP security denied oversized request body.', [
                'path' => $request->path(),
                'content_length' => $contentLength,
                'max_bytes' => $max,
                'client_ip' => $request->clientIp(),
            ], $request);

            throw new PayloadTooLargeException('HTTP request body is too large.');
        }
    }

    /**
     * @param array<array-key, mixed> $files
     */
    private function validateUploadSize(array $files): void
    {
        $max = $this->security->http()->maxUploadBytes();

        if ($max === 0) {
            return;
        }

        foreach ($this->fileSizes($files) as $size) {
            if ($size > $max) {
                $this->deny('oversized_upload', 'HTTP security denied oversized upload.', [
                    'upload_size' => $size,
                    'max_bytes' => $max,
                ]);

                throw new PayloadTooLargeException('HTTP upload is too large.');
            }
        }
    }

    private function withSecurityHeaders(HttpResponse $response): HttpResponse
    {
        if (!$this->security->http()->sendsSecurityHeaders()) {
            return $response;
        }

        $headers = $response->headers();

        foreach ($this->security->http()->headers() as $name => $value) {
            if ($response->header($name) === null) {
                $headers[$name] = $value;
            }
        }

        if ($this->security->http()->enforcesHttps() && $response->header('Strict-Transport-Security') === null) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function deny(string $reason, string $message, array $context, ?HttpRequest $request = null): void
    {
        $this->events?->dispatch(new HttpSecurityDenied($reason, $message, $context, $request));
    }

    private function effectiveHost(HttpRequest $request): string
    {
        if ($this->isTrustedProxy($request)) {
            return $request->forwardedHost() ?? $request->host();
        }

        return $request->host();
    }

    private function effectiveScheme(HttpRequest $request): string
    {
        if ($this->isTrustedProxy($request)) {
            return $request->forwardedScheme() ?? $request->scheme();
        }

        return $request->scheme();
    }

    private function isTrustedProxy(HttpRequest $request): bool
    {
        $trustedProxies = $this->security->http()->trustedProxies();

        return new TrustedProxyMatcher($trustedProxies)->matches($request->clientIp());
    }

    private function isLocalHost(string $host): bool
    {
        return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }

    /**
     * @param array<array-key, mixed> $files
     *
     * @return list<int>
     */
    private function fileSizes(array $files): array
    {
        $sizes = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $sizes[] = $file->size();

                continue;
            }

            if (!is_array($file)) {
                continue;
            }

            $size = $file['size'] ?? null;

            if (is_int($size)) {
                $sizes[] = $size;

                continue;
            }

            if (is_array($size)) {
                $sizes = [...$sizes, ...$this->flattenIntValues($size)];
            }

            $sizes = [...$sizes, ...$this->fileSizes($file)];
        }

        return $sizes;
    }

    /**
     * @param array<array-key, mixed> $values
     *
     * @return list<int>
     */
    private function flattenIntValues(array $values): array
    {
        $integers = [];

        foreach ($values as $value) {
            if (is_int($value)) {
                $integers[] = $value;

                continue;
            }

            if (is_array($value)) {
                $integers = [...$integers, ...$this->flattenIntValues($value)];
            }
        }

        return $integers;
    }
}
