<?php

declare(strict_types=1);

use LPWork\Security\Exceptions\InvalidSecurityConfigException;
use LPWork\Security\Exceptions\MissingSecurityConfigException;
use LPWork\Security\SecurityConfigFactory;

it('creates a development security config that allows local flows by default', function (): void {
    $config = new SecurityConfigFactory()->create([
        'environment' => 'development',
        'production_environments' => ['production'],
        'profiles' => [
            'development' => [
                'allow_local_flows' => true,
                'enforce_https' => false,
                'send_security_headers' => false,
                'secure_cookies' => false,
                'trusted_hosts' => [],
                'trusted_proxies' => [],
                'max_request_body_bytes' => 1024,
                'max_upload_bytes' => 2048,
                'header_profile' => 'none',
                'headers' => [],
                'csrf' => [
                    'enabled' => false,
                    'session_key' => '_csrf_token',
                    'input_key' => '_token',
                    'header_name' => 'X-CSRF-TOKEN',
                    'rotate' => false,
                    'per_form' => false,
                ],
            ],
            'production' => [
                'allow_local_flows' => false,
                'enforce_https' => true,
                'send_security_headers' => true,
                'secure_cookies' => true,
                'trusted_hosts' => ['example.com'],
                'trusted_proxies' => ['10.0.0.1'],
                'max_request_body_bytes' => 1024,
                'max_upload_bytes' => 2048,
                'header_profile' => 'default',
                'headers' => ['X-Content-Type-Options' => 'nosniff'],
                'csrf' => [
                    'enabled' => true,
                    'session_key' => '_csrf_token',
                    'input_key' => '_token',
                    'header_name' => 'X-CSRF-TOKEN',
                    'rotate' => true,
                    'per_form' => true,
                ],
            ],
        ],
    ]);

    expect($config->environment())->toBe('development')
        ->and($config->allowsLocalFlows())->toBeTrue()
        ->and($config->enforcesHttps())->toBeFalse()
        ->and($config->sendsSecurityHeaders())->toBeFalse()
        ->and($config->usesSecureCookies())->toBeFalse()
        ->and($config->http()->maxRequestBodyBytes())->toBe(1024)
        ->and($config->http()->maxUploadBytes())->toBe(2048)
        ->and($config->csrf()->enabled())->toBeFalse();
});

it('creates a production security config from stricter configured values', function (): void {
    $config = new SecurityConfigFactory()->create([
        'environment' => 'production',
        'production_environments' => ['production'],
        'profiles' => [
            'development' => [
                'allow_local_flows' => true,
                'enforce_https' => false,
                'send_security_headers' => false,
                'secure_cookies' => false,
                'trusted_hosts' => [],
                'trusted_proxies' => [],
                'max_request_body_bytes' => 1024,
                'max_upload_bytes' => 2048,
                'header_profile' => 'none',
                'headers' => [],
                'csrf' => [
                    'enabled' => false,
                    'session_key' => '_csrf_token',
                    'input_key' => '_token',
                    'header_name' => 'X-CSRF-TOKEN',
                    'rotate' => false,
                    'per_form' => false,
                ],
            ],
            'production' => [
                'allow_local_flows' => false,
                'enforce_https' => true,
                'send_security_headers' => true,
                'secure_cookies' => true,
                'trusted_hosts' => ['example.com'],
                'trusted_proxies' => ['10.0.0.1'],
                'max_request_body_bytes' => 1024,
                'max_upload_bytes' => 2048,
                'header_profile' => 'default',
                'headers' => ['X-Content-Type-Options' => 'nosniff'],
                'csrf' => [
                    'enabled' => true,
                    'session_key' => '_csrf_token',
                    'input_key' => '_token',
                    'header_name' => 'X-CSRF-TOKEN',
                    'rotate' => true,
                    'per_form' => true,
                ],
            ],
        ],
    ]);

    expect($config->environment())->toBe('production')
        ->and($config->allowsLocalFlows())->toBeFalse()
        ->and($config->enforcesHttps())->toBeTrue()
        ->and($config->sendsSecurityHeaders())->toBeTrue()
        ->and($config->usesSecureCookies())->toBeTrue()
        ->and($config->http()->trustedHosts())->toBe(['example.com'])
        ->and($config->http()->trustedProxies())->toBe(['10.0.0.1'])
        ->and($config->http()->headerProfile())->toBe('default')
        ->and($config->http()->headers())->toMatchArray([
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
        ])
        ->and($config->csrf()->enabled())->toBeTrue()
        ->and($config->csrf()->sessionKey())->toBe('_csrf_token')
        ->and($config->csrf()->inputKey())->toBe('_token')
        ->and($config->csrf()->headerName())->toBe('X-CSRF-TOKEN')
        ->and($config->csrf()->rotates())->toBeTrue()
        ->and($config->csrf()->usesPerFormTokens())->toBeTrue();
});

it('rejects missing security profiles', function (): void {
    expect(fn(): mixed => new SecurityConfigFactory()->create([
        'environment' => 'production',
        'production_environments' => ['production'],
        'profiles' => [
            'development' => [
                'allow_local_flows' => true,
                'enforce_https' => false,
                'send_security_headers' => false,
                'secure_cookies' => false,
                'trusted_hosts' => [],
                'trusted_proxies' => [],
                'max_request_body_bytes' => 1024,
                'max_upload_bytes' => 2048,
                'headers' => [],
                'csrf' => [
                    'enabled' => false,
                    'session_key' => '_csrf_token',
                    'input_key' => '_token',
                    'header_name' => 'X-CSRF-TOKEN',
                ],
            ],
        ],
    ]))->toThrow(MissingSecurityConfigException::class);
});

it('rejects invalid production environment declarations', function (): void {
    expect(fn(): mixed => new SecurityConfigFactory()->create([
        'environment' => 'production',
        'production_environments' => [''],
        'profiles' => [],
    ]))->toThrow(InvalidSecurityConfigException::class);
});

it('rejects invalid HTTP security header declarations', function (): void {
    expect(fn(): mixed => new SecurityConfigFactory()->create([
        'environment' => 'production',
        'production_environments' => ['production'],
        'profiles' => [
            'production' => [
                'allow_local_flows' => false,
                'enforce_https' => true,
                'send_security_headers' => true,
                'secure_cookies' => true,
                'trusted_hosts' => [],
                'trusted_proxies' => [],
                'max_request_body_bytes' => 1024,
                'max_upload_bytes' => 2048,
                'headers' => ['X-Frame-Options' => ''],
                'csrf' => [
                    'enabled' => false,
                    'session_key' => '_csrf_token',
                    'input_key' => '_token',
                    'header_name' => 'X-CSRF-TOKEN',
                ],
            ],
        ],
    ]))->toThrow(InvalidSecurityConfigException::class);
});

it('rejects invalid CSRF security declarations', function (): void {
    expect(fn(): mixed => new SecurityConfigFactory()->create([
        'environment' => 'production',
        'production_environments' => ['production'],
        'profiles' => [
            'production' => [
                'allow_local_flows' => false,
                'enforce_https' => true,
                'send_security_headers' => true,
                'secure_cookies' => true,
                'trusted_hosts' => [],
                'trusted_proxies' => [],
                'max_request_body_bytes' => 1024,
                'max_upload_bytes' => 2048,
                'headers' => [],
                'csrf' => [
                    'enabled' => true,
                    'session_key' => '',
                    'input_key' => '_token',
                    'header_name' => 'X-CSRF-TOKEN',
                ],
            ],
        ],
    ]))->toThrow(InvalidSecurityConfigException::class);
});
