<?php

declare(strict_types=1);

namespace LPWork\Storage\Drivers;

use Closure;
use LPWork\Filesystem\Filesystem;
use LPWork\Shared\Http\HttpClient;
use LPWork\Storage\Contracts\StorageDriver;
use LPWork\Storage\Exceptions\StorageFileNotFoundException;
use RuntimeException;

/**
 * Represents the s3 storage driver framework component.
 */
final readonly class S3StorageDriver implements StorageDriver
{
    /**
     * Creates a new S3StorageDriver instance.
     */
    public function __construct(
        private string $bucket,
        private string $region,
        private string $accessKey,
        private string $secretKey,
        private string $endpoint,
        private bool $pathStyle = true,
        private Filesystem $filesystem = new Filesystem(),
        private HttpClient $http = new HttpClient(),
    ) {}

    /**
     * Reports whether exists.
     */
    public function exists(string $path): bool
    {
        return $this->request('HEAD', $path)->successful();
    }

    /**
     * Returns the requested value from this component.
     */
    public function get(string $path): string
    {
        $response = $this->request('GET', $path);

        if ($response->status === 404) {
            throw new StorageFileNotFoundException($path);
        }

        if (!$response->successful()) {
            throw new RuntimeException(sprintf('Could not read S3 object [%s].', $path));
        }

        return $response->body;
    }

    /**
     * Stores or replaces a value in this component's backing store.
     */
    public function put(string $path, string $contents): void
    {
        $response = $this->request('PUT', $path, $contents);

        if (!$response->successful()) {
            throw new RuntimeException(sprintf('Could not write S3 object [%s].', $path));
        }
    }

    /**
     * Registers or stores put if missing.
     */
    public function putIfMissing(string $path, string $contents): bool
    {
        if ($this->exists($path)) {
            return false;
        }

        $this->put($path, $contents);

        return true;
    }

    /**
     * Registers or stores append.
     */
    public function append(string $path, string $contents): void
    {
        $current = $this->exists($path) ? $this->get($path) : '';
        $this->put($path, $current . $contents);
    }

    /**
     * Deletes the requested value from this component's backing store.
     */
    public function delete(string $path): void
    {
        $this->request('DELETE', $path);
    }

    /**
     * Clears the state owned by this component.
     */
    public function clear(string $path): void
    {
        foreach ($this->list($path) as $key) {
            $this->delete($key);
        }
    }

    /**
     * Returns a copy with with exclusive lock applied.
     */
    public function withExclusiveLock(string $path, Closure $callback): mixed
    {
        return $callback();
    }

    /**
     * @return list<string>
     */
    private function list(string $path): array
    {
        $path = $this->filesystem->normalizeRelativePath($path);
        $prefix = $path === '.' ? '' : rtrim($path, '/') . '/';
        $response = $this->request('GET', '?list-type=2&prefix=' . rawurlencode($prefix));

        if (!$response->successful()) {
            return [];
        }

        preg_match_all('/<Key>([^<]+)<\/Key>/', $response->body, $matches);

        return array_map(static fn(string $key): string => html_entity_decode($key, ENT_QUOTES | ENT_XML1), $matches[1]);
    }

    private function request(string $method, string $path, string $body = ''): \LPWork\Shared\Http\HttpResponse
    {
        $path = str_starts_with($path, '?') ? $path : '/' . $this->filesystem->normalizeRelativePath($path);
        $url = $this->url($path);
        $parsedPath = parse_url($url, PHP_URL_PATH);
        $parsedQuery = parse_url($url, PHP_URL_QUERY);
        $headers = $this->headers(
            $method,
            is_string($parsedPath) && $parsedPath !== '' ? $parsedPath : '/',
            is_string($parsedQuery) ? $parsedQuery : '',
            $body,
        );

        return $this->http->request($method, $url, $headers, $body);
    }

    private function url(string $path): string
    {
        $endpoint = rtrim($this->endpoint, '/');

        if ($this->pathStyle) {
            return $endpoint . '/' . $this->bucket . $path;
        }

        $parts = parse_url($endpoint);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? $endpoint;
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        return "{$scheme}://{$this->bucket}.{$host}{$port}{$path}";
    }

    /**
     * @return array<string, string>
     */
    private function headers(string $method, string $uri, string $query, string $body): array
    {
        $now = gmdate('Ymd\THis\Z');
        $date = substr($now, 0, 8);
        $payloadHash = hash('sha256', $body);
        $parsedHost = parse_url($this->endpoint, PHP_URL_HOST);
        $host = is_string($parsedHost) ? $parsedHost : $this->endpoint;
        $headers = [
            'Host' => $host,
            'x-amz-content-sha256' => $payloadHash,
            'x-amz-date' => $now,
        ];
        $signedHeaders = 'host;x-amz-content-sha256;x-amz-date';
        $canonicalHeaders = sprintf("host:%s\nx-amz-content-sha256:%s\nx-amz-date:%s\n", $host, $payloadHash, $now);
        $canonicalRequest = implode("\n", [$method, $uri, $query, $canonicalHeaders, $signedHeaders, $payloadHash]);
        $scope = "{$date}/{$this->region}/s3/aws4_request";
        $stringToSign = implode("\n", ['AWS4-HMAC-SHA256', $now, $scope, hash('sha256', $canonicalRequest)]);
        $signature = hash_hmac('sha256', $stringToSign, $this->signingKey($date));
        $headers['Authorization'] = sprintf(
            'AWS4-HMAC-SHA256 Credential=%s/%s, SignedHeaders=%s, Signature=%s',
            $this->accessKey,
            $scope,
            $signedHeaders,
            $signature,
        );

        return $headers;
    }

    private function signingKey(string $date): string
    {
        $dateKey = hash_hmac('sha256', $date, 'AWS4' . $this->secretKey, binary: true);
        $regionKey = hash_hmac('sha256', $this->region, $dateKey, binary: true);
        $serviceKey = hash_hmac('sha256', 's3', $regionKey, binary: true);

        return hash_hmac('sha256', 'aws4_request', $serviceKey, binary: true);
    }
}
