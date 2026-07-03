<?php

declare(strict_types=1);

namespace LPWork\Session\Drivers;

use LPWork\Database\Contracts\Connection;
use LPWork\Database\SqlIdentifier;
use LPWork\Time\Contracts\Clock;
use LPWork\Time\SystemClock;

/**
 * Represents the database session driver framework component.
 */
final class DatabaseSessionDriver extends PersistentSessionDriver
{
    /**
     * Creates a new DatabaseSessionDriver instance.
     */
    public function __construct(
        private readonly Connection $connection,
        string $table,
        private readonly Clock $clock = new SystemClock(),
        string $name = 'LPWORK_SESSION',
        int $lifetime = 120,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = true,
        string $sameSite = 'Lax',
    ) {
        parent::__construct($name, $lifetime, $path, $domain, $secure, $httpOnly, $sameSite);
        $this->table = SqlIdentifier::table($table);
    }

    private readonly string $table;

    protected function read(string $id): ?array
    {
        $row = $this->connection->query(
            sprintf('select payload, expires_at from %s where id = ?', $this->table),
            [$id],
        )->first();

        if (!is_array($row)) {
            return null;
        }

        if ($this->intValue($row['expires_at'] ?? null) <= $this->now()) {
            $this->delete($id);

            return null;
        }

        $payload = $row['payload'] ?? null;
        $data = is_string($payload) ? unserialize($payload) : null;

        return $this->sessionData($data);
    }

    protected function write(string $id, array $data, int $ttlSeconds): void
    {
        $this->delete($id);
        $this->connection->statement(
            sprintf('insert into %s (id, payload, expires_at, updated_at) values (?, ?, ?, ?)', $this->table),
            [$id, serialize($data), $this->now() + $ttlSeconds, $this->now()],
        );
    }

    protected function delete(string $id): void
    {
        $this->connection->statement(sprintf('delete from %s where id = ?', $this->table), [$id]);
    }

    private function now(): int
    {
        return $this->clock->now()->getTimestamp();
    }

    private function intValue(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^[+-]?\d+$/', $value) === 1) {
            return (int) $value;
        }

        return 0;
    }
}
