<?php

declare(strict_types=1);

namespace LPWork\Notifications;

use function array_is_list;
use function json_decode;
use function json_encode;

use JsonException;
use LPWork\Database\Contracts\Connection;
use LPWork\Database\SqlIdentifier;
use LPWork\Notifications\Exceptions\InvalidNotificationStorageException;

/**
 * Represents the notification database repository framework component.
 */
final readonly class NotificationDatabaseRepository
{
    /**
     * Creates a new NotificationDatabaseRepository instance.
     */
    public function __construct(
        private Connection $db,
        string $table,
    ) {
        $this->table = SqlIdentifier::table($table);
    }

    private string $table;

    /**
     * @param array<string, mixed> $data
     */
    public function store(string $notifiableType, string $notifiableId, string $notification, array $data, int $now): string
    {
        $id = bin2hex(random_bytes(16));

        try {
            $payload = json_encode($data, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw InvalidNotificationStorageException::invalidPayload($exception);
        }

        $this->db->statement(
            sprintf('insert into %s (id, notifiable_type, notifiable_id, notification_class, data, read_at, created_at, updated_at) values (?, ?, ?, ?, ?, null, ?, ?)', $this->table),
            [$id, $notifiableType, $notifiableId, $notification, $payload, $now, $now],
        );

        return $id;
    }

    /**
     * @return list<DatabaseNotificationRecord>
     */
    public function forNotifiable(string $notifiableType, string $notifiableId): array
    {
        return $this->records($notifiableType, $notifiableId, unreadOnly: false);
    }

    /**
     * @return list<DatabaseNotificationRecord>
     */
    public function unreadForNotifiable(string $notifiableType, string $notifiableId): array
    {
        return $this->records($notifiableType, $notifiableId, unreadOnly: true);
    }

    /**
     * Performs the mark as read operation.
     */
    public function markAsRead(string $id, int $now): int
    {
        return $this->db->statement(
            sprintf('update %s set read_at = ?, updated_at = ? where id = ? and read_at is null', $this->table),
            [$now, $now, $id],
        );
    }

    /**
     * Performs the mark all as read operation.
     */
    public function markAllAsRead(string $notifiableType, string $notifiableId, int $now): int
    {
        return $this->db->statement(
            sprintf('update %s set read_at = ?, updated_at = ? where notifiable_type = ? and notifiable_id = ? and read_at is null', $this->table),
            [$now, $now, $notifiableType, $notifiableId],
        );
    }

    /**
     * @return list<DatabaseNotificationRecord>
     */
    private function records(string $notifiableType, string $notifiableId, bool $unreadOnly): array
    {
        $where = $unreadOnly ? ' and read_at is null' : '';
        $rows = $this->db->select(
            sprintf('select * from %s where notifiable_type = ? and notifiable_id = ?%s order by created_at desc', $this->table, $where),
            [$notifiableType, $notifiableId],
        );
        $records = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                throw InvalidNotificationStorageException::invalidRecord();
            }

            $records[] = $this->record($row);
        }

        return $records;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function record(array $row): DatabaseNotificationRecord
    {
        try {
            $payload = $this->stringValue($row, 'data');
            $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw InvalidNotificationStorageException::invalidPayload($exception);
        }

        $data = $this->associativeArray($data);

        return new DatabaseNotificationRecord(
            id: $this->stringValue($row, 'id'),
            notifiableType: $this->stringValue($row, 'notifiable_type'),
            notifiableId: $this->stringValue($row, 'notifiable_id'),
            notification: $this->stringValue($row, 'notification_class'),
            data: $data,
            readAt: $this->optionalIntValue($row, 'read_at'),
            createdAt: $this->intValue($row, 'created_at'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function associativeArray(mixed $value): array
    {
        if (!is_array($value) || array_is_list($value)) {
            throw InvalidNotificationStorageException::invalidRecord();
        }

        $data = [];

        foreach ($value as $key => $item) {
            if (!is_string($key)) {
                throw InvalidNotificationStorageException::invalidRecord();
            }

            $data[$key] = $item;
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function stringValue(array $row, string $key): string
    {
        $value = $row[$key] ?? null;

        if (!is_string($value)) {
            throw InvalidNotificationStorageException::invalidRecord();
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function optionalIntValue(array $row, string $key): ?int
    {
        $value = $row[$key] ?? null;

        if ($value === null) {
            return null;
        }

        if (!is_int($value)) {
            throw InvalidNotificationStorageException::invalidRecord();
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function intValue(array $row, string $key): int
    {
        $value = $row[$key] ?? null;

        if (!is_int($value)) {
            throw InvalidNotificationStorageException::invalidRecord();
        }

        return $value;
    }
}
