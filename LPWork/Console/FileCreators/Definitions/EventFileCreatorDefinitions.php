<?php

declare(strict_types=1);

namespace LPWork\Console\FileCreators\Definitions;

use LPWork\Console\FileCreators\FileCreatorDefinition;

/**
 * Represents the event file creator definitions framework component.
 */
final readonly class EventFileCreatorDefinitions implements FileCreatorDefinitionGroup
{
    /**
     * Returns all registered values for this component.
     */
    public function all(): array
    {
        return [
            new FileCreatorDefinition(
                type: 'event',
                description: 'Create an event object.',
                defaultDirectory: 'App/Events',
                suffix: '',
                template: <<<'PHP'
                    <?php

                    declare(strict_types=1);

                    namespace {{ namespace }};

                    final readonly class {{ class }}
                    {
                    }
                    PHP,
            ),
            new FileCreatorDefinition(
                type: 'broadcast-event',
                description: 'Create a broadcastable event object.',
                defaultDirectory: 'App/Events',
                suffix: 'Event',
                template: <<<'PHP'
                    <?php

                    declare(strict_types=1);

                    namespace {{ namespace }};

                    use LPWork\Broadcasting\Contracts\BroadcastableEvent;

                    final readonly class {{ class }} implements BroadcastableEvent
                    {
                        /**
                         * @return list<string>
                         */
                        public function broadcastChannels(): array
                        {
                            return ['{{ channel_name }}'];
                        }

                        /**
                         * @return array<string, mixed>
                         */
                        public function broadcastPayload(): array
                        {
                            return [
                                'event' => $this->broadcastName(),
                            ];
                        }

                        public function broadcastName(): string
                        {
                            return '{{ broadcast_name }}';
                        }
                    }
                    PHP,
            ),
            new FileCreatorDefinition(
                type: 'listener',
                description: 'Create an event listener.',
                defaultDirectory: 'App/Listeners',
                suffix: 'Listener',
                template: <<<'PHP'
                    <?php

                    declare(strict_types=1);

                    namespace {{ namespace }};

                    final readonly class {{ class }}
                    {
                        public function __invoke(object $event): void
                        {
                        }
                    }
                    PHP,
            ),
        ];
    }
}
