<?php

declare(strict_types=1);

namespace LPWork\Locks;

use LPWork\Database\Contracts\Connection;
use LPWork\Database\SqlIdentifier;
use LPWork\Locks\Contracts\AtomicLock;
use LPWork\Time\Contracts\Clock;

/**
 * Represents the database atomic lock framework component.
 */
final readonly class DatabaseAtomicLock implements AtomicLock
{
    /**
     * Creates a new DatabaseAtomicLock instance.
     */
    public function __construct(
        private Connection $connection,
        string $table,
        private Clock $clock,
        private string $name,
        private string $owner,
        private int $ttlSeconds,
    ) {
        $this->table = SqlIdentifier::table($table);
    }

    private string $table;

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Performs the owner operation.
     */
    public function owner(): string
    {
        return $this->owner;
    }

    /**
     * Performs the acquire operation.
     */
    public function acquire(): bool
    {
        return $this->connection->transaction(function (): bool {
            $now = $this->now();
            $this->connection->statement(sprintf('delete from %s where name = ? and expires_at <= ?', $this->table), [$this->name, $now]);
            $row = $this->connection->query(sprintf('select name from %s where name = ?', $this->table), [$this->name])->first();

            if (is_array($row)) {
                return false;
            }

            $this->connection->statement(
                sprintf('insert into %s (name, owner, expires_at, created_at) values (?, ?, ?, ?)', $this->table),
                [$this->name, $this->owner, $now + $this->ttlSeconds, $now],
            );

            return true;
        });
    }

    /**
     * Removes or clears release.
     */
    public function release(): bool
    {
        return $this->connection->statement(
            sprintf('delete from %s where name = ? and owner = ?', $this->table),
            [$this->name, $this->owner],
        ) > 0;
    }

    private function now(): int
    {
        return $this->clock->now()->getTimestamp();
    }
}
