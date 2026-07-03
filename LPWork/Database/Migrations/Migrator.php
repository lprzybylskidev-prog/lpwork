<?php

declare(strict_types=1);

namespace LPWork\Database\Migrations;

use LPWork\Database\Contracts\Connection;
use LPWork\Database\DatabaseManager;
use LPWork\Database\Migrations\Contracts\Migration;
use LPWork\Database\Migrations\Contracts\RunsOutsideTransaction;
use LPWork\Database\Migrations\Exceptions\MigrationConnectionNotRegisteredException;

/**
 * Represents the migrator framework component.
 */
final readonly class Migrator
{
    /**
     * Creates a new Migrator instance.
     */
    public function __construct(
        private DatabaseManager $database,
        private MigrationRegistry $registry,
        private MigrationResolver $resolver,
    ) {}

    /**
     * @return list<MigrationExecution>
     */
    public function migrate(?string $connection = null, bool $all = false): array
    {
        $executions = [];

        foreach ($this->targetConnections($connection, $all) as $target) {
            $db = $this->databaseConnection($target);
            $repository = new MigrationRepository($db);
            $records = $repository->records();
            $pending = array_values(array_filter(
                $this->registry->forConnection($target),
                static fn(string $migration): bool => !array_key_exists($migration, $records),
            ));

            if ($pending === []) {
                continue;
            }

            $batch = $repository->nextBatch();

            foreach ($pending as $migrationClass) {
                $migration = $this->resolver->resolve($migrationClass);
                $this->run($db, $migration, up: true);
                $repository->record($migrationClass, $batch);
                $executions[] = new MigrationExecution($target, $migrationClass, $batch);
            }
        }

        return $executions;
    }

    /**
     * @return list<MigrationExecution>
     */
    public function rollback(?string $connection = null, bool $all = false): array
    {
        $executions = [];

        foreach ($this->targetConnections($connection, $all) as $target) {
            $db = $this->databaseConnection($target);
            $repository = new MigrationRepository($db);
            $batch = $repository->latestBatch();

            if ($batch === 0) {
                continue;
            }

            $executions = [
                ...$executions,
                ...$this->rollbackBatch($target, $db, $repository, $batch),
            ];
        }

        return $executions;
    }

    /**
     * Performs the fresh operation.
     */
    public function fresh(?string $connection = null, bool $all = false): MigrationFreshResult
    {
        $rolledBack = [];
        $migrated = [];
        $targets = $this->targetConnections($connection, $all);

        foreach ($targets as $target) {
            $rolledBack = [
                ...$rolledBack,
                ...$this->rollbackAll($target),
            ];
        }

        foreach ($targets as $target) {
            $migrated = [
                ...$migrated,
                ...$this->migrate($target),
            ];
        }

        return new MigrationFreshResult($rolledBack, $migrated);
    }

    /**
     * @return list<MigrationStatus>
     */
    public function status(?string $connection = null, bool $all = false): array
    {
        $statuses = [];

        foreach ($this->targetConnections($connection, $all) as $target) {
            $records = new MigrationRepository($this->databaseConnection($target))->records();

            foreach ($this->registry->forConnection($target) as $migration) {
                $record = $records[$migration] ?? null;
                $statuses[] = new MigrationStatus(
                    connection: $target,
                    migration: $migration,
                    ran: $record !== null,
                    batch: $record?->batch,
                    executedAt: $record?->executedAt,
                );
            }
        }

        return $statuses;
    }

    /**
     * @return list<string>
     */
    private function targetConnections(?string $connection, bool $all): array
    {
        if ($all) {
            return $this->registry->connectionNames();
        }

        $target = $connection ?? 'default';

        if (!$this->registry->hasConnection($target)) {
            throw new MigrationConnectionNotRegisteredException($target);
        }

        return [$target];
    }

    private function databaseConnection(string $connection): Connection
    {
        if ($connection === 'default') {
            return $this->database->default();
        }

        return $this->database->connection($connection);
    }

    /**
     * @return list<MigrationExecution>
     */
    private function rollbackAll(string $connection): array
    {
        $executions = [];
        $db = $this->databaseConnection($connection);
        $repository = new MigrationRepository($db);

        while (($batch = $repository->latestBatch()) > 0) {
            $batchExecutions = $this->rollbackBatch($connection, $db, $repository, $batch);

            if ($batchExecutions === []) {
                break;
            }

            $executions = [
                ...$executions,
                ...$batchExecutions,
            ];
        }

        return $executions;
    }

    /**
     * @return list<MigrationExecution>
     */
    private function rollbackBatch(string $connection, Connection $db, MigrationRepository $repository, int $batch): array
    {
        $executions = [];
        $records = $repository->recordsForBatch($batch);
        $registered = $this->registry->forConnection($connection);
        $rollback = array_reverse(array_values(array_filter(
            $registered,
            static fn(string $migration): bool => array_key_exists($migration, $records),
        )));

        foreach ($rollback as $migrationClass) {
            $migration = $this->resolver->resolve($migrationClass);
            $this->run($db, $migration, up: false);
            $repository->delete($migrationClass);
            $executions[] = new MigrationExecution($connection, $migrationClass, $batch);
        }

        return $executions;
    }

    private function run(Connection $db, Migration $migration, bool $up): void
    {
        $callback = static function (Connection $db) use ($migration, $up): void {
            if ($up) {
                $migration->up($db);

                return;
            }

            $migration->down($db);
        };

        if ($migration instanceof RunsOutsideTransaction) {
            $callback($db);

            return;
        }

        $db->transaction(static function (Connection $db) use ($callback): void {
            $callback($db);
        });
    }
}
