<?php

declare(strict_types=1);

namespace LPWork\Console\FileCreators\Definitions;

use LPWork\Console\FileCreators\FileCreatorDefinition;
use LPWork\Console\FileCreators\ProviderRegistration;

/**
 * Represents the broadcasting file creator definitions framework component.
 */
final readonly class BroadcastingFileCreatorDefinitions implements FileCreatorDefinitionGroup
{
    /**
     * Returns all registered values for this component.
     */
    public function all(): array
    {
        return [
            new FileCreatorDefinition(
                type: 'broadcast-channel-provider',
                description: 'Create a broadcast channel provider.',
                defaultDirectory: 'App/Broadcasting',
                suffix: 'Provider',
                template: <<<'PHP'
                    <?php

                    declare(strict_types=1);

                    namespace {{ namespace }};

                    use LPWork\Broadcasting\BroadcastChannelRegistry;
                    use LPWork\Broadcasting\Providers\BroadcastChannelsProvider;

                    final class {{ class }} extends BroadcastChannelsProvider
                    {
                        protected function channels(BroadcastChannelRegistry $channels): void
                        {
                            $channels->public('{{ channel_name }}');
                        }
                    }
                    PHP,
                registration: ProviderRegistration::list('App/AppServiceProvider.php', 'serviceProviders'),
            ),
        ];
    }
}
