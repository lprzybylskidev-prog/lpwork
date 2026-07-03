<?php

declare(strict_types=1);

namespace App\Shared\Configs;

use LPWork\Config\Contracts\ConfigDefinition;
use LPWork\Config\Contracts\EnvironmentRequirementProvider;
use LPWork\Config\EnvironmentRequirement;
use LPWork\Environment\Environment;

/**
 * Configures rate-limit storage and default HTTP/CLI throttling policies.
 */
final class ThrottleConfig implements ConfigDefinition, EnvironmentRequirementProvider
{
    public function key(): string
    {
        return 'throttle';
    }

    /**
     * @return array<array-key, mixed>
     */
    public function values(): array
    {
        return [
            // THROTTLE_STORAGE selects the throttle backend; cache storage uses the configured cache store.
            'storage' => Environment::getString('THROTTLE_STORAGE'),
            'store' => Environment::get('THROTTLE_CACHE_STORE', Environment::get('CACHE_STORE', 'framework')),
            'policies' => [
                // Web and API policies are applied by the configured HTTP middleware groups.
                'http_web' => [
                    'enabled' => Environment::getBool('THROTTLE_HTTP_WEB_ENABLED'),
                    'max_attempts' => Environment::getInt('THROTTLE_HTTP_WEB_MAX_ATTEMPTS'),
                    'decay_seconds' => Environment::getInt('THROTTLE_HTTP_WEB_DECAY_SECONDS'),
                ],
                'http_api' => [
                    'enabled' => Environment::getBool('THROTTLE_HTTP_API_ENABLED'),
                    'max_attempts' => Environment::getInt('THROTTLE_HTTP_API_MAX_ATTEMPTS'),
                    'decay_seconds' => Environment::getInt('THROTTLE_HTTP_API_DECAY_SECONDS'),
                ],
                // CLI throttling protects commands that opt into the throttle middleware.
                'cli' => [
                    'enabled' => Environment::getBool('THROTTLE_CLI_ENABLED'),
                    'max_attempts' => Environment::getInt('THROTTLE_CLI_MAX_ATTEMPTS'),
                    'decay_seconds' => Environment::getInt('THROTTLE_CLI_DECAY_SECONDS'),
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
            EnvironmentRequirement::nonEmptyString('THROTTLE_STORAGE'),
            EnvironmentRequirement::bool('THROTTLE_HTTP_WEB_ENABLED'),
            EnvironmentRequirement::int('THROTTLE_HTTP_WEB_MAX_ATTEMPTS'),
            EnvironmentRequirement::int('THROTTLE_HTTP_WEB_DECAY_SECONDS'),
            EnvironmentRequirement::bool('THROTTLE_HTTP_API_ENABLED'),
            EnvironmentRequirement::int('THROTTLE_HTTP_API_MAX_ATTEMPTS'),
            EnvironmentRequirement::int('THROTTLE_HTTP_API_DECAY_SECONDS'),
            EnvironmentRequirement::bool('THROTTLE_CLI_ENABLED'),
            EnvironmentRequirement::int('THROTTLE_CLI_MAX_ATTEMPTS'),
            EnvironmentRequirement::int('THROTTLE_CLI_DECAY_SECONDS'),
        ];
    }
}
