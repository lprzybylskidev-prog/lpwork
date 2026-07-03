<?php

declare(strict_types=1);

namespace LPWork\Notifications;

/**
 * Represents the result of notification channel result work.
 */
final readonly class NotificationChannelResult
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public string $channel,
        public string $status = 'sent',
        public array $context = [],
    ) {}
}
