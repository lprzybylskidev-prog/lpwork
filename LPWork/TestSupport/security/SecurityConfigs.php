<?php

declare(strict_types=1);

namespace Tests\support\security;

use LPWork\Security\Csrf\CsrfConfig;
use LPWork\Security\Http\HttpSecurityConfig;
use LPWork\Security\SecurityConfig;

final class SecurityConfigs
{
    /**
     * @param list<string> $trustedHosts
     * @param list<string> $trustedProxies
     * @param array<string, string> $headers
     */
    public static function http(
        bool $allowLocalFlows = false,
        bool $enforceHttps = false,
        bool $sendSecurityHeaders = false,
        bool $secureCookies = false,
        array $trustedHosts = [],
        array $trustedProxies = [],
        int $maxRequestBodyBytes = 1024,
        int $maxUploadBytes = 1024,
        array $headers = [],
        bool $csrfEnabled = false,
        bool $csrfRotate = false,
        bool $csrfPerForm = false,
    ): SecurityConfig {
        return new SecurityConfig(
            environment: 'testing',
            allowLocalFlows: $allowLocalFlows,
            http: new HttpSecurityConfig(
                enforceHttps: $enforceHttps,
                sendSecurityHeaders: $sendSecurityHeaders,
                secureCookies: $secureCookies,
                trustedHosts: $trustedHosts,
                trustedProxies: $trustedProxies,
                maxRequestBodyBytes: $maxRequestBodyBytes,
                maxUploadBytes: $maxUploadBytes,
                headerProfile: 'none',
                headers: $headers,
            ),
            csrf: new CsrfConfig(
                enabled: $csrfEnabled,
                sessionKey: '_csrf_token',
                inputKey: '_token',
                headerName: 'X-CSRF-TOKEN',
                rotate: $csrfRotate,
                perForm: $csrfPerForm,
            ),
        );
    }
}
