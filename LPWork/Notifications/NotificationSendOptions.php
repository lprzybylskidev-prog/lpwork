<?php

declare(strict_types=1);

namespace LPWork\Notifications;

/**
 * Carries options for notification send options behavior.
 */
final readonly class NotificationSendOptions
{
    /**
     * @param null|list<string> $channels
     */
    public function __construct(
        public ?array $channels = null,
        public bool $queue = true,
    ) {}
}
