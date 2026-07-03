<?php

declare(strict_types=1);

namespace App\Shared\Configs;

use LPWork\Config\Contracts\ConfigDefinition;
use LPWork\Config\Contracts\EnvironmentRequirementProvider;
use LPWork\Config\EnvironmentRequirement;
use LPWork\Environment\Environment;

/**
 * Defines security profiles, request limits, HTTPS/cookie policies, and CSRF defaults.
 */
final class SecurityConfig implements ConfigDefinition, EnvironmentRequirementProvider
{
    public function key(): string
    {
        return 'security';
    }

    /**
     * @return array<array-key, mixed>
     */
    public function values(): array
    {
        return [
            // APP_KEY signs and encrypts framework security values; never commit a real production key.
            'environment' => Environment::getString('APP_ENV'),
            'app_key' => Environment::getString('APP_KEY'),
            // Production detection drives CLI safety checks and production security posture.
            'production_environments' => ['production'],
            'profiles' => [
                // Development keeps local flows easy to run while preserving request-size and CSRF controls.
                'development' => [
                    'allow_local_flows' => true,
                    'enforce_https' => false,
                    'send_security_headers' => false,
                    'secure_cookies' => false,
                    'trusted_hosts' => [],
                    'trusted_proxies' => [],
                    'max_request_body_bytes' => Environment::getInt('SECURITY_MAX_REQUEST_BODY_BYTES'),
                    'max_upload_bytes' => Environment::getInt('SECURITY_MAX_UPLOAD_BYTES'),
                    'header_profile' => 'none',
                    'headers' => [],
                    'csrf' => [
                        'enabled' => Environment::getBool('SECURITY_CSRF_ENABLED'),
                        'session_key' => '_csrf_token',
                        'input_key' => '_token',
                        'header_name' => 'X-CSRF-TOKEN',
                        'rotate' => false,
                        'per_form' => false,
                    ],
                ],
                // Production can enforce HTTPS, secure cookies, security headers, and stricter CSRF behavior.
                'production' => [
                    'allow_local_flows' => false,
                    'enforce_https' => Environment::getBool('SECURITY_ENFORCE_HTTPS'),
                    'send_security_headers' => Environment::getBool('SECURITY_SEND_HEADERS'),
                    'secure_cookies' => Environment::getBool('SECURITY_SECURE_COOKIES'),
                    'trusted_hosts' => [],
                    'trusted_proxies' => [],
                    'max_request_body_bytes' => Environment::getInt('SECURITY_MAX_REQUEST_BODY_BYTES'),
                    'max_upload_bytes' => Environment::getInt('SECURITY_MAX_UPLOAD_BYTES'),
                    'header_profile' => 'default',
                    'headers' => [],
                    'csrf' => [
                        'enabled' => Environment::getBool('SECURITY_CSRF_ENABLED'),
                        'session_key' => '_csrf_token',
                        'input_key' => '_token',
                        'header_name' => 'X-CSRF-TOKEN',
                        'rotate' => true,
                        'per_form' => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return list<EnvironmentRequirement>
     */
    public function environmentRequirements(): array
    {
        return [
            EnvironmentRequirement::nonEmptyString('APP_ENV'),
            EnvironmentRequirement::nonEmptyString('APP_KEY'),
            EnvironmentRequirement::int('SECURITY_MAX_REQUEST_BODY_BYTES'),
            EnvironmentRequirement::int('SECURITY_MAX_UPLOAD_BYTES'),
            EnvironmentRequirement::bool('SECURITY_CSRF_ENABLED'),
            EnvironmentRequirement::bool('SECURITY_ENFORCE_HTTPS'),
            EnvironmentRequirement::bool('SECURITY_SEND_HEADERS'),
            EnvironmentRequirement::bool('SECURITY_SECURE_COOKIES'),
        ];
    }
}
