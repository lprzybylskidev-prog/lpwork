<?php

declare(strict_types=1);

namespace LPWork\Schedule;

use LPWork\Database\Contracts\Connection;
use LPWork\Database\SqlIdentifier;
use LPWork\Time\Contracts\Clock;

/**
 * Represents the schedule store framework component.
 */
final readonly class ScheduleStore
{
    /**
     * Creates a new ScheduleStore instance.
     */
    public function __construct(
        private Connection $connection,
        private Clock $clock,
        string $runsTable = 'schedule_runs',
        private bool $historyEnabled = true,
    ) {
        $this->runsTable = SqlIdentifier::table($runsTable);
    }

    private string $runsTable;

    /**
     * Performs the record run operation.
     */
    public function recordRun(ScheduledTask $task, string $status, int $startedAt, int $finishedAt, ?int $exitCode, ?string $message): void
    {
        if (!$this->historyEnabled) {
            return;
        }

        $this->connection->statement(
            sprintf('insert into %s (id, task_name, task_type, target, status, exit_code, message, started_at, finished_at, created_at) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', $this->runsTable),
            [bin2hex(random_bytes(16)), $task->name, $task->type->value, $task->target, $status, $exitCode, $message, $startedAt, $finishedAt, $this->now()],
        );
    }

    /**
     * Removes or clears prune run history.
     */
    public function pruneRunHistory(int $olderThanSeconds): int
    {
        if (!$this->historyEnabled) {
            return 0;
        }

        return $this->connection->statement(
            sprintf('delete from %s where created_at < ?', $this->runsTable),
            [$this->now() - $olderThanSeconds],
        );
    }

    /**
     * @return list<array<string, mixed>|object>
     */
    public function runs(): array
    {
        return $this->connection->select(sprintf('select * from %s order by id asc', $this->runsTable));
    }

    private function now(): int
    {
        return $this->clock->now()->getTimestamp();
    }
}
