<?php

declare(strict_types=1);

namespace Tests\support\testing\Http;

use LPWork\Responses\HttpResponse;

final class CookieJar
{
    /**
     * @var array<string, StoredCookie>
     */
    private array $cookies = [];

    public function put(string $name, string $value, string $path = '/', string $domain = '', bool $secure = false): self
    {
        $this->store(new StoredCookie($name, $value, $path, $domain, $secure));

        return $this;
    }

    public function forget(string $name, string $path = '/', string $domain = ''): self
    {
        unset($this->cookies[$this->key($name, $domain, $path)]);

        return $this;
    }

    public function clear(): self
    {
        $this->cookies = [];

        return $this;
    }

    public function capture(HttpResponse $response): void
    {
        foreach ($response->cookies() as $cookie) {
            $this->store(StoredCookie::fromResponseCookie($cookie));
        }
    }

    /**
     * @return array<string, string>
     */
    public function requestCookies(string $uri, bool $secure = false): array
    {
        $host = $this->host($uri);
        $path = $this->path($uri);
        $cookies = [];

        foreach ($this->cookies as $cookie) {
            if ($cookie->matches($host, $path, $secure)) {
                $cookies[$cookie->name] = $cookie->requestValue();
            }
        }

        return $cookies;
    }

    public function has(string $name, string $path = '/', string $domain = ''): bool
    {
        return array_key_exists($this->key($name, $domain, $path), $this->cookies);
    }

    public function get(string $name, string $path = '/', string $domain = ''): ?StoredCookie
    {
        return $this->cookies[$this->key($name, $domain, $path)] ?? null;
    }

    /**
     * @return array<string, StoredCookie>
     */
    public function all(): array
    {
        return $this->cookies;
    }

    private function store(StoredCookie $cookie): void
    {
        if ($cookie->expired()) {
            $this->forget($cookie->name, $cookie->path, $cookie->domain);

            return;
        }

        $this->cookies[$cookie->storageKey()] = $cookie;
    }

    private function key(string $name, string $domain, string $path): string
    {
        return $name . '|' . $domain . '|' . $path;
    }

    private function host(string $uri): string
    {
        $host = parse_url($uri, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : 'localhost';
    }

    private function path(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH);

        return is_string($path) && $path !== '' ? $path : '/';
    }
}
