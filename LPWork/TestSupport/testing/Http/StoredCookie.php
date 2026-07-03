<?php

declare(strict_types=1);

namespace Tests\support\testing\Http;

use LPWork\Http\Cookie;

final readonly class StoredCookie
{
    public function __construct(
        public string $name,
        public string $value,
        public string $path = '/',
        public string $domain = '',
        public bool $secure = false,
        public bool $httpOnly = true,
        public string $sameSite = 'Lax',
        public ?int $maxAge = null,
    ) {}

    public static function fromResponseCookie(Cookie $cookie): self
    {
        $parts = array_map(trim(...), explode(';', $cookie->toHeader()));
        $nameValue = array_shift($parts);
        [$name, $value] = array_pad(explode('=', $nameValue, 2), 2, '');
        $attributes = [
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => false,
            'samesite' => 'Lax',
            'max-age' => null,
        ];

        foreach ($parts as $part) {
            [$rawName, $rawValue] = array_pad(explode('=', $part, 2), 2, '');
            $attributeName = strtolower($rawName);

            if ($attributeName === 'secure' || $attributeName === 'httponly') {
                $attributes[$attributeName] = true;

                continue;
            }

            if (array_key_exists($attributeName, $attributes)) {
                $attributes[$attributeName] = $rawValue;
            }
        }

        $maxAge = $attributes['max-age'];

        return new self(
            name: rawurldecode($name),
            value: rawurldecode($value),
            path: $attributes['path'],
            domain: $attributes['domain'],
            secure: $attributes['secure'] === true,
            httpOnly: $attributes['httponly'] === true,
            sameSite: $attributes['samesite'],
            maxAge: is_string($maxAge) && preg_match('/^-?\d+$/', $maxAge) === 1 ? (int) $maxAge : null,
        );
    }

    public function expired(): bool
    {
        return $this->maxAge !== null && $this->maxAge < 0;
    }

    public function matches(string $host, string $path, bool $secure): bool
    {
        if ($this->secure && !$secure) {
            return false;
        }

        if ($this->domain !== '' && !$this->matchesDomain($host)) {
            return false;
        }

        return str_starts_with($path, $this->path);
    }

    public function requestValue(): string
    {
        return $this->value;
    }

    public function storageKey(): string
    {
        return $this->name . '|' . $this->domain . '|' . $this->path;
    }

    private function matchesDomain(string $host): bool
    {
        $domain = ltrim(strtolower($this->domain), '.');
        $host = strtolower($host);

        return $host === $domain || str_ends_with($host, '.' . $domain);
    }
}
