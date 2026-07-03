<?php

declare(strict_types=1);

namespace LPWork\Database\Migrations;

use DateTimeImmutable;
use LPWork\Database\Contracts\Connection;

/**
 * Represents the migration repository framework component.
 */
final readonly class MigrationRepository
{
    /**
     * Creates a new MigrationRepository instance.
     */
    public function __construct(
        private Connection $connection,
    ) {}

    /**
     * Performs the ensure storage operation.
     */
    public function ensureStorage(): void
    {
        $this->connection->statement(
            'create table if not exists migrations (migration varchar(255) primary key, batch integer not null, executed_at varchar(32) not null)',
        );
    }

    /**
     * @return array<string, MigrationRecord>
     */
    public function records(): array
    {
        $this->ensureStorage();
        $records = [];

        foreach ($this->connection->select('select migration, batch, executed_at from migrations order by batch asc, migration asc') as $row) {
            if (!is_array($row)) {
                continue;
            }

            $migration = $row['migration'] ?? null;
            $batch = $row['batch'] ?? null;
            $executedAt = $row['executed_at'] ?? null;

            if (is_string($migration) && (is_int($batch) || is_string($batch)) && is_string($executedAt)) {
                $records[$migration] = new MigrationRecord($migration, (int) $batch, $executedAt);
            }
        }

        return $records;
    }

    /**
     * Performs the latest batch operation.
     */
    public function latestBatch(): int
    {
        $this->ensureStorage();
        $batch = $this->connection->query('select max(batch) from migrations')->value();

        return is_numeric($batch) ? (int) $batch : 0;
    }

    /**
     * Performs the next batch operation.
     */
    public function nextBatch(): int
    {
        return $this->latestBatch() + 1;
    }

    /**
     * @return array<string, MigrationRecord>
     */
    public function recordsForBatch(int $batch): array
    {
        return array_filter(
            $this->records(),
            static fn(MigrationRecord $record): bool => $record->batch === $batch,
        );
    }

    /**
     * Performs the record operation.
     */
    public function record(string $migration, int $batch): void
    {
        $this->ensureStorage();
        $this->connection->statement(
            'insert into migrations (migration, batch, executed_at) values (?, ?, ?)',
            [$migration, $batch, new DateTimeImmutable()->format(DATE_ATOM)],
        );
    }

    /**
     * Deletes the requested value from this component's backing store.
     */
    public function delete(string $migration): void
    {
        $this->ensureStorage();
        $this->connection->statement('delete from migrations where migration = ?', [$migration]);
    }
}
