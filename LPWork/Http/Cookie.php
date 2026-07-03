<?php

declare(strict_types=1);

namespace LPWork\Http;

use LPWork\Http\Exceptions\InvalidCookieException;

/**
 * Represents the cookie framework component.
 */
final readonly class Cookie
{
    /**
     * Creates a new Cookie instance.
     */
    public function __construct(
        private string $name,
        private string $value = '',
        private int $maxAge = 0,
        private string $path = '/',
        private string $domain = '',
        private bool $secure = false,
        private bool $httpOnly = true,
        private string $sameSite = 'Lax',
    ) {
        if ($this->name === '' || preg_match('/[=,; \t\r\n]/', $this->name) === 1) {
            throw InvalidCookieException::name();
        }

        if (!in_array($this->sameSite, ['Lax', 'Strict', 'None'], true)) {
            throw InvalidCookieException::sameSite();
        }
    }

    /**
     * Removes a value from this component's backing store.
     */
    public static function forget(string $name, string $path = '/', string $domain = ''): self
    {
        return new self($name, '', maxAge: -3600, path: $path, domain: $domain);
    }

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Converts this value to to header output.
     */
    public function toHeader(): string
    {
        $parts = [
            rawurlencode($this->name) . '=' . rawurlencode($this->value),
            'Path=' . $this->path,
            'SameSite=' . $this->sameSite,
        ];

        if ($this->maxAge !== 0) {
            $parts[] = 'Max-Age=' . $this->maxAge;
        }

        if ($this->maxAge < 0) {
            $parts[] = 'Expires=Thu, 01 Jan 1970 00:00:00 GMT';
        }

        if ($this->domain !== '') {
            $parts[] = 'Domain=' . $this->domain;
        }

        if ($this->secure) {
            $parts[] = 'Secure';
        }

        if ($this->httpOnly) {
            $parts[] = 'HttpOnly';
        }

        return implode('; ', $parts);
    }
}
