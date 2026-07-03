<?php

declare(strict_types=1);

namespace LPWork\Notifications;

/**
 * Represents the database notification record framework component.
 */
final readonly class DatabaseNotificationRecord
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public string $id,
        public string $notifiableType,
        public string $notifiableId,
        public string $notification,
        public array $data,
        public ?int $readAt,
        public int $createdAt,
    ) {}

    /**
     * Reports whether is read.
     */
    public function isRead(): bool
    {
        return $this->readAt !== null;
    }
}
