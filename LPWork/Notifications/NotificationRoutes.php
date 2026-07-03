<?php

declare(strict_types=1);

namespace LPWork\Notifications;

use LPWork\Mail\MailAddress;

/**
 * Represents the notification routes framework component.
 */
final readonly class NotificationRoutes
{
    /**
     * @param list<string> $broadcastChannels
     */
    public function __construct(
        private ?MailAddress $mail = null,
        private ?string $databaseId = null,
        private ?string $databaseType = null,
        private array $broadcastChannels = [],
    ) {}

    /**
     * Creates a new value for this component.
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Performs the mail operation.
     */
    public function mail(string|MailAddress $address, ?string $name = null): self
    {
        return new self(
            mail: $address instanceof MailAddress ? $address : new MailAddress($address, $name),
            databaseId: $this->databaseId,
            databaseType: $this->databaseType,
            broadcastChannels: $this->broadcastChannels,
        );
    }

    /**
     * Returns database.
     */
    public function database(string $id, ?string $type = null): self
    {
        return new self(
            mail: $this->mail,
            databaseId: $id,
            databaseType: $type,
            broadcastChannels: $this->broadcastChannels,
        );
    }

    /**
     * @param list<string> $channels
     */
    public function broadcast(array $channels): self
    {
        return new self(
            mail: $this->mail,
            databaseId: $this->databaseId,
            databaseType: $this->databaseType,
            broadcastChannels: $channels,
        );
    }

    /**
     * Performs the mail route operation.
     */
    public function mailRoute(): ?MailAddress
    {
        return $this->mail;
    }

    /**
     * Returns database id.
     */
    public function databaseId(): ?string
    {
        return $this->databaseId;
    }

    /**
     * Returns database type.
     */
    public function databaseType(): ?string
    {
        return $this->databaseType;
    }

    /**
     * @return list<string>
     */
    public function broadcastChannels(): array
    {
        return $this->broadcastChannels;
    }
}
