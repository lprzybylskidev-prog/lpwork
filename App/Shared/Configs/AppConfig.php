<?php

declare(strict_types=1);

namespace App\Shared\Configs;

use LPWork\Config\Contracts\ConfigDefinition;
use LPWork\Config\Contracts\EnvironmentRequirementProvider;
use LPWork\Config\EnvironmentRequirement;
use LPWork\Environment\Environment;

/**
 * Defines the application identity, locale, URL, debug mode, and timezone.
 */
final class AppConfig implements ConfigDefinition, EnvironmentRequirementProvider
{
    public function key(): string
    {
        return 'app';
    }

    /**
     * @return array<array-key, mixed>
     */
    public function values(): array
    {
        return [
            // Core application metadata used by diagnostics, URLs, localization, and time services.
            'env' => Environment::getString('APP_ENV'),
            'name' => Environment::getString('APP_NAME'),
            'debug' => Environment::getBool('APP_DEBUG'),
            'url' => Environment::getString('APP_URL'),
            'lang' => Environment::getString('APP_LANG'),
            'timezone' => Environment::getString('APP_TIMEZONE'),
        ];
    }

    /**
     * @return list<EnvironmentRequirement>
     */
    public function environmentRequirements(): array
    {
        return [
            EnvironmentRequirement::nonEmptyString('APP_ENV'),
            EnvironmentRequirement::nonEmptyString('APP_NAME'),
            EnvironmentRequirement::bool('APP_DEBUG'),
            EnvironmentRequirement::nonEmptyString('APP_URL'),
            EnvironmentRequirement::nonEmptyString('APP_LANG'),
            EnvironmentRequirement::nonEmptyString('APP_TIMEZONE'),
        ];
    }
}
