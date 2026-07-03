<?php

declare(strict_types=1);

namespace LPWork\Queue;

use LPWork\Database\Contracts\Connection;
use LPWork\Database\SqlIdentifier;
use LPWork\Queue\Enums\QueueJobStatus;
use LPWork\Queue\Exceptions\InvalidQueueStorageRecordException;
use LPWork\Time\Contracts\Clock;

/**
 * Represents the database queue repository framework component.
 */
final readonly class DatabaseQueueRepository
{
    /**
     * Creates a new DatabaseQueueRepository instance.
     */
    public function __construct(
        private Connection $connection,
        private Clock $clock,
        string $table = 'queue_jobs',
    ) {
        $this->table = SqlIdentifier::table($table);
    }

    private string $table;

    /**
     * Performs assert ready.
     */
    public function assertReady(): void
    {
        $this->connection->select(sprintf('select id from %s where 1 = 0', $this->table));
    }

    /**
     * Registers or stores push.
     */
    public function push(QueuedJobPayload $payload): void
    {
        $now = $this->now();
        $this->connection->statement(
            sprintf('insert into %s (id, queue, job_class, payload, status, attempts, max_attempts, available_at, reserved_until, completed_at, failed_at, last_error, created_at, updated_at) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', $this->table),
            [
                $payload->id,
                $payload->queue,
                $payload->jobClass,
                $payload->body,
                QueueJobStatus::Pending->value,
                0,
                $payload->maxAttempts,
                $payload->availableAt,
                null,
                null,
                null,
                null,
                $payload->createdAt,
                $now,
            ],
        );
    }

    /**
     * Performs the reserve operation.
     */
    public function reserve(string $queue, int $retryAfterSeconds): ?ReservedJob
    {
        $now = $this->now();

        return $this->connection->transaction(function (Connection $connection) use ($queue, $retryAfterSeconds, $now): ?ReservedJob {
            $row = $connection->query(
                sprintf('select * from %s where queue = ? and ((status = ? and available_at <= ?) or (status = ? and reserved_until is not null and reserved_until <= ?)) order by available_at asc, created_at asc limit 1', $this->table),
                [$queue, QueueJobStatus::Pending->value, $now, QueueJobStatus::Reserved->value, $now],
            )->first();

            if (!is_array($row)) {
                return null;
            }

            $attempts = $this->rowInt($row, 'attempts') + 1;
            $connection->statement(
                sprintf('update %s set status = ?, attempts = ?, reserved_until = ?, updated_at = ? where id = ?', $this->table),
                [QueueJobStatus::Reserved->value, $attempts, $now + $retryAfterSeconds, $now, $this->rowString($row, 'id')],
            );

            return $this->reservedJob($row, $attempts);
        });
    }

    /**
     * Removes or clears release.
     */
    public function release(ReservedJob $job, int $delaySeconds): void
    {
        $now = $this->now();
        $this->connection->statement(
            sprintf('update %s set status = ?, available_at = ?, reserved_until = null, updated_at = ? where id = ?', $this->table),
            [QueueJobStatus::Pending->value, $now + $delaySeconds, $now, $job->driverId],
        );
    }

    /**
     * Performs the complete operation.
     */
    public function complete(ReservedJob $job): void
    {
        $now = $this->now();
        $this->connection->statement(
            sprintf('update %s set status = ?, reserved_until = null, completed_at = ?, updated_at = ? where id = ?', $this->table),
            [QueueJobStatus::Completed->value, $now, $now, $job->driverId],
        );
    }

    /**
     * Performs the fail operation.
     */
    public function fail(ReservedJob $job, string $exception): void
    {
        $now = $this->now();
        $this->connection->statement(
            sprintf('update %s set status = ?, reserved_until = null, failed_at = ?, last_error = ?, updated_at = ? where id = ?', $this->table),
            [QueueJobStatus::Failed->value, $now, $exception, $now, $job->driverId],
        );
    }

    /**
     * Removes or clears prune completed.
     */
    public function pruneCompleted(int $olderThanSeconds): int
    {
        return $this->connection->statement(
            sprintf('delete from %s where status = ? and completed_at is not null and completed_at < ?', $this->table),
            [QueueJobStatus::Completed->value, $this->now() - $olderThanSeconds],
        );
    }

    /**
     * Removes or clears prune failed.
     */
    public function pruneFailed(int $olderThanSeconds): int
    {
        return $this->connection->statement(
            sprintf('delete from %s where status = ? and failed_at is not null and failed_at < ?', $this->table),
            [QueueJobStatus::Failed->value, $this->now() - $olderThanSeconds],
        );
    }

    /**
     * Clears the state owned by this component.
     */
    public function clear(string $queue): int
    {
        return $this->connection->statement(
            sprintf('delete from %s where queue = ? and status in (?, ?)', $this->table),
            [$queue, QueueJobStatus::Pending->value, QueueJobStatus::Reserved->value],
        );
    }

    /**
     * @return list<array<string, mixed>|object>
     */
    public function all(): array
    {
        return $this->connection->select(sprintf('select * from %s order by created_at asc', $this->table));
    }

    /**
     * @param array<string, mixed> $row
     */
    private function reservedJob(array $row, int $attempts): ReservedJob
    {
        return new ReservedJob(
            payload: new QueuedJobPayload(
                id: $this->rowString($row, 'id'),
                queue: $this->rowString($row, 'queue'),
                jobClass: $this->rowString($row, 'job_class'),
                body: $this->rowString($row, 'payload'),
                maxAttempts: $this->rowInt($row, 'max_attempts'),
                availableAt: $this->rowInt($row, 'available_at'),
                createdAt: $this->rowInt($row, 'created_at'),
            ),
            attempts: $attempts,
            driverId: $this->rowString($row, 'id'),
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function rowString(array $row, string $field): string
    {
        $value = $row[$field] ?? null;

        if (!is_string($value) || $value === '') {
            throw new InvalidQueueStorageRecordException($field);
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function rowInt(array $row, string $field): int
    {
        $value = $row[$field] ?? null;

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^[+-]?\d+$/', $value) === 1) {
            return (int) $value;
        }

        throw new InvalidQueueStorageRecordException($field);
    }

    private function now(): int
    {
        return $this->clock->now()->getTimestamp();
    }
}
