<?php

declare(strict_types=1);

namespace App\Shared\Configs;

use App\AppServiceProvider;

use function array_values;
use function is_a;

use LPWork\Config\Contracts\ConfigDefinition;
use LPWork\Config\Contracts\ConfigDefinitionProvider;
use LPWork\Config\Providers\ConfigsProvider as BaseConfigsProvider;
use LPWork\Foundation\Providers\ProviderServiceProvider;

/**
 * Registers application config definitions and module-provided config definitions.
 */
final class ConfigsProvider extends BaseConfigsProvider
{
    /**
     * @return list<class-string<ConfigDefinition>>
     */
    protected function configDefinitions(): array
    {
        return [
            // Shared application config files are loaded explicitly so application structure stays predictable.
            AppConfig::class,
            BroadcastingConfig::class,
            CacheConfig::class,
            DatabaseConfig::class,
            ErrorConfig::class,
            LockConfig::class,
            LoggingConfig::class,
            MailConfig::class,
            MaintenanceConfig::class,
            NotificationsConfig::class,
            ObservabilityConfig::class,
            QueueConfig::class,
            RoutingConfig::class,
            ScheduleConfig::class,
            SessionConfig::class,
            SecurityConfig::class,
            StorageConfig::class,
            ThrottleConfig::class,
            ViewConfig::class,
        ];
    }

    /**
     * @return list<class-string<ConfigDefinitionProvider>>
     */
    protected function configDefinitionProviders(): array
    {
        // Module providers can contribute config definitions through ConfigDefinitionProvider.
        return $this->configDefinitionProvidersFrom(new AppServiceProvider());
    }

    /**
     * @return list<class-string<ConfigDefinitionProvider>>
     */
    private function configDefinitionProvidersFrom(ProviderServiceProvider $provider): array
    {
        $configProviders = [];

        foreach ($provider->providerClasses() as $providerClass) {
            if (is_a($providerClass, ConfigDefinitionProvider::class, true)) {
                $configProviders[$providerClass] = $providerClass;

                continue;
            }

            if (!is_a($providerClass, ProviderServiceProvider::class, true)) {
                continue;
            }

            /** @var ProviderServiceProvider $childProvider */
            $childProvider = new $providerClass();

            foreach ($this->configDefinitionProvidersFrom($childProvider) as $configProvider) {
                $configProviders[$configProvider] = $configProvider;
            }
        }

        return array_values($configProviders);
    }
}
