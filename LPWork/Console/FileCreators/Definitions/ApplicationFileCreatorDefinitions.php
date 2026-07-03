<?php

declare(strict_types=1);

namespace LPWork\Console\FileCreators\Definitions;

use LPWork\Console\FileCreators\FileCreatorDefinition;
use LPWork\Console\FileCreators\ProviderRegistration;

/**
 * Represents the application file creator definitions framework component.
 */
final readonly class ApplicationFileCreatorDefinitions implements FileCreatorDefinitionGroup
{
    /**
     * Returns all registered values for this component.
     */
    public function all(): array
    {
        return [
            new FileCreatorDefinition(
                type: 'config',
                description: 'Create a config definition.',
                defaultDirectory: 'App/Shared/Configs',
                suffix: 'Config',
                template: <<<'PHP'
                    <?php

                    declare(strict_types=1);

                    namespace {{ namespace }};

                    use LPWork\Config\Contracts\ConfigDefinition;

                    final readonly class {{ class }} implements ConfigDefinition
                    {
                        public function key(): string
                        {
                            return '{{ config_key }}';
                        }

                        /**
                         * @return array<array-key, mixed>
                         */
                        public function values(): array
                        {
                            return [];
                        }
                    }
                    PHP,
                registration: ProviderRegistration::list('App/Shared/Configs/ConfigsProvider.php', 'configDefinitions'),
            ),
            new FileCreatorDefinition(
                type: 'job',
                description: 'Create a queued job.',
                defaultDirectory: 'App/Jobs',
                suffix: 'Job',
                template: <<<'PHP'
                    <?php

                    declare(strict_types=1);

                    namespace {{ namespace }};

                    final readonly class {{ class }}
                    {
                        public function handle(): void
                        {
                        }
                    }
                    PHP,
            ),
            new FileCreatorDefinition(
                type: 'health-check',
                description: 'Create a health check.',
                defaultDirectory: 'App/Health/Checks',
                suffix: 'HealthCheck',
                template: <<<'PHP'
                    <?php

                    declare(strict_types=1);

                    namespace {{ namespace }};

                    use LPWork\Health\Contracts\HealthCheck;
                    use LPWork\Health\HealthCheckResult;

                    final readonly class {{ class }} implements HealthCheck
                    {
                        public function name(): string
                        {
                            return '{{ health_name }}';
                        }

                        public function check(): HealthCheckResult
                        {
                            return HealthCheckResult::healthy($this->name());
                        }
                    }
                    PHP,
            ),
            new FileCreatorDefinition(
                type: 'service-provider',
                description: 'Create an application service provider.',
                defaultDirectory: 'App/Providers',
                suffix: 'Provider',
                template: <<<'PHP'
                    <?php

                    declare(strict_types=1);

                    namespace {{ namespace }};

                    use LPWork\Container\Container;
                    use LPWork\Foundation\ServiceProvider;

                    final class {{ class }} extends ServiceProvider
                    {
                        public function register(Container $container): void
                        {
                        }
                    }
                    PHP,
                registration: ProviderRegistration::list('App/AppServiceProvider.php', 'serviceProviders'),
            ),
        ];
    }
}
