<?php

declare(strict_types=1);

namespace App\Shared\Configs;

use LPWork\Config\Contracts\ConfigDefinition;
use LPWork\Config\Contracts\EnvironmentRequirementProvider;
use LPWork\Config\EnvironmentRequirement;
use LPWork\Environment\Environment;

/**
 * Maps PHP error handling flags and framework production error rendering settings.
 */
final class ErrorConfig implements ConfigDefinition, EnvironmentRequirementProvider
{
    public function key(): string
    {
        return 'error';
    }

    /**
     * @return array<array-key, mixed>
     */
    public function values(): array
    {
        return [
            // PHP error reporting/display flags are read from environment values so deployments can harden output.
            'log_directory' => Environment::getString('ERROR_LOG_DIRECTORY'),
            'reporting' => Environment::getInt('ERROR_REPORTING'),
            'display' => Environment::getInt('ERROR_DISPLAY'),
            'display_startup' => Environment::getInt('ERROR_DISPLAY_STARTUP'),
            'log' => Environment::getInt('ERROR_LOG'),
            // Set a route name here when production error pages should link back into the application.
            'production_route' => null,
        ];
    }

    /**
     * @return list<EnvironmentRequirement>
     */
    public function environmentRequirements(): array
    {
        return [
            EnvironmentRequirement::nonEmptyString('ERROR_LOG_DIRECTORY'),
            EnvironmentRequirement::int('ERROR_REPORTING'),
            EnvironmentRequirement::int('ERROR_DISPLAY'),
            EnvironmentRequirement::int('ERROR_DISPLAY_STARTUP'),
            EnvironmentRequirement::int('ERROR_LOG'),
        ];
    }
}
