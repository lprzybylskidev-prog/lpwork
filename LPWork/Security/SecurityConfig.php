<?php

declare(strict_types=1);

namespace LPWork\Security;

use LPWork\Security\Csrf\CsrfConfig;
use LPWork\Security\Http\HttpSecurityConfig;

/**
 * Represents security config configuration.
 */
final readonly class SecurityConfig
{
    /**
     * Creates a new SecurityConfig instance.
     */
    public function __construct(
        private string $environment,
        private bool $allowLocalFlows,
        private HttpSecurityConfig $http,
        private CsrfConfig $csrf,
    ) {}

    /**
     * Returns environment.
     */
    public function environment(): string
    {
        return $this->environment;
    }

    /**
     * Reports whether allows local flows.
     */
    public function allowsLocalFlows(): bool
    {
        return $this->allowLocalFlows;
    }

    /**
     * Reports whether enforces https.
     */
    public function enforcesHttps(): bool
    {
        return $this->http->enforcesHttps();
    }

    /**
     * Reports whether sends security headers.
     */
    public function sendsSecurityHeaders(): bool
    {
        return $this->http->sendsSecurityHeaders();
    }

    /**
     * Reports whether uses secure cookies.
     */
    public function usesSecureCookies(): bool
    {
        return $this->http->usesSecureCookies();
    }

    /**
     * Performs the http operation.
     */
    public function http(): HttpSecurityConfig
    {
        return $this->http;
    }

    /**
     * Performs the csrf operation.
     */
    public function csrf(): CsrfConfig
    {
        return $this->csrf;
    }
}
