<?php

declare(strict_types=1);

namespace LPWork\Notifications;

use LPWork\Queue\QueueDispatchOptions;

/**
 * Carries options for notification queue options behavior.
 */
final readonly class NotificationQueueOptions
{
    /**
     * Creates a new NotificationQueueOptions instance.
     */
    public function __construct(
        public ?string $connection = null,
        public ?string $queue = null,
        public int $delaySeconds = 0,
        public ?int $maxAttempts = null,
    ) {}

    /**
     * Converts this value to to queue dispatch options output.
     */
    public function toQueueDispatchOptions(): QueueDispatchOptions
    {
        return new QueueDispatchOptions(
            connection: $this->connection,
            queue: $this->queue,
            delaySeconds: $this->delaySeconds,
            maxAttempts: $this->maxAttempts,
        );
    }
}
