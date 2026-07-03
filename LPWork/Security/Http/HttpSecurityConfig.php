<?php

declare(strict_types=1);

namespace LPWork\Security\Http;

/**
 * Represents http security config configuration.
 */
final readonly class HttpSecurityConfig
{
    /**
     * @param list<string> $trustedHosts
     * @param list<string> $trustedProxies
     * @param array<string, string> $headers
     */
    public function __construct(
        private bool $enforceHttps,
        private bool $sendSecurityHeaders,
        private bool $secureCookies,
        private array $trustedHosts,
        private array $trustedProxies,
        private int $maxRequestBodyBytes,
        private int $maxUploadBytes,
        private string $headerProfile,
        private array $headers,
    ) {}

    /**
     * Reports whether enforces https.
     */
    public function enforcesHttps(): bool
    {
        return $this->enforceHttps;
    }

    /**
     * Reports whether sends security headers.
     */
    public function sendsSecurityHeaders(): bool
    {
        return $this->sendSecurityHeaders;
    }

    /**
     * Reports whether uses secure cookies.
     */
    public function usesSecureCookies(): bool
    {
        return $this->secureCookies;
    }

    /**
     * @return list<string>
     */
    public function trustedHosts(): array
    {
        return $this->trustedHosts;
    }

    /**
     * @return list<string>
     */
    public function trustedProxies(): array
    {
        return $this->trustedProxies;
    }

    /**
     * Performs the max request body bytes operation.
     */
    public function maxRequestBodyBytes(): int
    {
        return $this->maxRequestBodyBytes;
    }

    /**
     * Performs the max upload bytes operation.
     */
    public function maxUploadBytes(): int
    {
        return $this->maxUploadBytes;
    }

    /**
     * Performs the header profile operation.
     */
    public function headerProfile(): string
    {
        return $this->headerProfile;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }
}
