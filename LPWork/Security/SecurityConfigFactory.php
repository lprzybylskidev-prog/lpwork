<?php

declare(strict_types=1);

namespace LPWork\Security;

use LPWork\Config\ArrayConfigReader;
use LPWork\Foundation\Exceptions\InvalidRuntimeEnvironmentException;
use LPWork\Foundation\RuntimeEnvironment;
use LPWork\Security\Csrf\CsrfConfig;
use LPWork\Security\Exceptions\InvalidSecurityConfigException;
use LPWork\Security\Exceptions\MissingSecurityConfigException;
use LPWork\Security\Http\HttpSecurityConfig;
use LPWork\Security\Http\SecurityHeaderProfile;

/**
 * Creates security config factory instances from framework configuration.
 */
final readonly class SecurityConfigFactory
{
    /**
     * @param array<array-key, mixed> $config
     */
    public function create(array $config): SecurityConfig
    {
        $reader = $this->reader($config);
        $environment = $reader->string('environment');
        $profile = $this->profileName($environment, $reader->stringList('production_environments'));
        $profiles = $reader->arrayMap('profiles');

        if (!array_key_exists($profile, $profiles)) {
            throw new MissingSecurityConfigException("profiles.{$profile}");
        }

        $profileReader = $this->reader($profiles[$profile]);

        return new SecurityConfig(
            environment: $environment,
            allowLocalFlows: $profileReader->bool('allow_local_flows', "profiles.{$profile}.allow_local_flows"),
            http: new HttpSecurityConfig(
                enforceHttps: $profileReader->bool('enforce_https', "profiles.{$profile}.enforce_https"),
                sendSecurityHeaders: $profileReader->bool('send_security_headers', "profiles.{$profile}.send_security_headers"),
                secureCookies: $profileReader->bool('secure_cookies', "profiles.{$profile}.secure_cookies"),
                trustedHosts: $profileReader->stringList('trusted_hosts', "profiles.{$profile}.trusted_hosts"),
                trustedProxies: $profileReader->stringList('trusted_proxies', "profiles.{$profile}.trusted_proxies"),
                maxRequestBodyBytes: $profileReader->int('max_request_body_bytes', "profiles.{$profile}.max_request_body_bytes"),
                maxUploadBytes: $profileReader->int('max_upload_bytes', "profiles.{$profile}.max_upload_bytes"),
                headerProfile: $profileReader->optionalString('header_profile', "profiles.{$profile}.header_profile") ?? 'none',
                headers: $this->headers($profileReader, $profile),
            ),
            csrf: $this->csrfConfig($profileReader->array('csrf', "profiles.{$profile}.csrf"), "profiles.{$profile}.csrf"),
        );
    }

    /**
     * @param list<string> $productionEnvironments
     */
    private function profileName(string $environment, array $productionEnvironments): string
    {
        try {
            return new RuntimeEnvironment($environment, $productionEnvironments)->isProduction()
                ? 'production'
                : 'development';
        } catch (InvalidRuntimeEnvironmentException) {
            throw new InvalidSecurityConfigException('production_environments');
        }
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private function reader(array $config): ArrayConfigReader
    {
        return new ArrayConfigReader(
            config: $config,
            missingException: static fn(string $key): MissingSecurityConfigException => new MissingSecurityConfigException($key),
            invalidException: static fn(string $key): InvalidSecurityConfigException => new InvalidSecurityConfigException($key),
        );
    }

    /**
     * @return array<string, string>
     */
    private function headers(ArrayConfigReader $reader, string $profile): array
    {
        $headerProfile = $reader->optionalString('header_profile', "profiles.{$profile}.header_profile") ?? 'none';
        $headers = match ($headerProfile) {
            'none' => [],
            'default' => SecurityHeaderProfile::default(),
            default => throw new InvalidSecurityConfigException("profiles.{$profile}.header_profile"),
        };

        return [
            ...$headers,
            ...$reader->stringMap('headers', "profiles.{$profile}.headers"),
        ];
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private function csrfConfig(array $config, string $key): CsrfConfig
    {
        $reader = $this->reader($config);

        return new CsrfConfig(
            enabled: $reader->bool('enabled', "{$key}.enabled"),
            sessionKey: $reader->string('session_key', "{$key}.session_key"),
            inputKey: $reader->string('input_key', "{$key}.input_key"),
            headerName: $reader->string('header_name', "{$key}.header_name"),
            rotate: $reader->optionalBool('rotate', "{$key}.rotate") ?? false,
            perForm: $reader->optionalBool('per_form', "{$key}.per_form") ?? false,
        );
    }
}
