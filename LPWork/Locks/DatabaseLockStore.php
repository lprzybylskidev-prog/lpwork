<?php

declare(strict_types=1);

namespace LPWork\Locks;

use LPWork\Database\Contracts\Connection;
use LPWork\Database\SqlIdentifier;
use LPWork\Locks\Contracts\AtomicLock;
use LPWork\Locks\Contracts\LockStore;
use LPWork\Time\Contracts\Clock;
use LPWork\Time\SystemClock;

/**
 * Represents the database lock store framework component.
 */
final readonly class DatabaseLockStore implements LockStore
{
    /**
     * Creates a new DatabaseLockStore instance.
     */
    public function __construct(
        private Connection $connection,
        string $table = 'locks',
        private Clock $clock = new SystemClock(),
    ) {
        $this->table = SqlIdentifier::table($table);
    }

    private string $table;

    /**
     * Performs the lock operation.
     */
    public function lock(string $name, int $ttlSeconds): AtomicLock
    {
        return new DatabaseAtomicLock($this->connection, $this->table, $this->clock, $name, bin2hex(random_bytes(16)), $ttlSeconds);
    }
}
