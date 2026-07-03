<?php

declare(strict_types=1);

namespace LPWork\Console\FileCreators\Definitions;

use LPWork\Console\FileCreators\FileCreatorDefinition;

/**
 * Represents the notification file creator definitions framework component.
 */
final readonly class NotificationFileCreatorDefinitions implements FileCreatorDefinitionGroup
{
    /**
     * Returns all registered values for this component.
     */
    public function all(): array
    {
        return [
            new FileCreatorDefinition(
                type: 'notification',
                description: 'Create a mail notification.',
                defaultDirectory: 'App/Notifications',
                suffix: 'Notification',
                template: <<<'PHP'
                    <?php

                    declare(strict_types=1);

                    namespace {{ namespace }};

                    use LPWork\Mail\MailMessage;
                    use LPWork\Notifications\Contracts\MailNotification;
                    use LPWork\Notifications\Contracts\Notifiable;

                    final readonly class {{ class }} implements MailNotification
                    {
                        /**
                         * @return list<string>
                         */
                        public function channels(object $notifiable): array
                        {
                            return ['mail'];
                        }

                        public function toMail(Notifiable $notifiable): MailMessage
                        {
                            return MailMessage::create()
                                ->subject('{{ notification_subject }}')
                                ->text('{{ class }} notification.');
                        }
                    }
                    PHP,
            ),
        ];
    }
}
