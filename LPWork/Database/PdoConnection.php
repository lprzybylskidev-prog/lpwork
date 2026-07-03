<?php

declare(strict_types=1);

namespace LPWork\Database;

use LPWork\Database\Contracts\Connection;
use LPWork\Database\Contracts\QueryReporter;
use LPWork\Database\Enums\FetchMode;
use LPWork\Database\Exceptions\DatabaseQueryException;
use PDO;
use PDOException;
use Throwable;

/**
 * Represents the pdo connection framework component.
 */
final class PdoConnection implements Connection
{
    private int $transactionLevel = 0;

    /**
     * Creates a new PdoConnection instance.
     */
    public function __construct(
        private readonly string $name,
        private readonly PDO $pdo,
        private readonly QueryReporter $reporter = new NullQueryReporter(),
    ) {}

    /**
     * @param array<array-key, mixed> $bindings
     * @return list<array<string, mixed>|object>
     */
    public function select(string $sql, array $bindings = [], FetchMode $fetchMode = FetchMode::Associative): array
    {
        return $this->query($sql, $bindings)->all($fetchMode);
    }

    /**
     * @param array<array-key, mixed> $bindings
     */
    public function statement(string $sql, array $bindings = []): int
    {
        return $this->query($sql, $bindings)->affectedRows();
    }

    /**
     * @param array<array-key, mixed> $bindings
     */
    public function query(string $sql, array $bindings = []): QueryResult
    {
        $started = hrtime(true);

        try {
            $statement = $this->pdo->prepare($sql);
            $statement->execute($bindings);
            $this->report($sql, $bindings, $started, successful: true);

            return new QueryResult($statement);
        } catch (PDOException $exception) {
            $queryException = DatabaseQueryException::failed($this->name, $sql, $bindings, $exception);
            $this->report($sql, $bindings, $started, successful: false, exception: $queryException);

            throw $queryException;
        }
    }

    /**
     * Performs the begin transaction operation.
     */
    public function beginTransaction(): void
    {
        if ($this->transactionLevel === 0) {
            $this->pdo->beginTransaction();
        } else {
            $this->pdo->exec($this->savepointStatement('SAVEPOINT'));
        }

        $this->transactionLevel++;
    }

    /**
     * Performs the commit operation.
     */
    public function commit(): void
    {
        if ($this->transactionLevel <= 0) {
            return;
        }

        $this->transactionLevel--;

        if ($this->transactionLevel === 0) {
            $this->pdo->commit();

            return;
        }

        $this->pdo->exec($this->savepointStatement('RELEASE SAVEPOINT'));
    }

    /**
     * Performs the roll back operation.
     */
    public function rollBack(): void
    {
        if ($this->transactionLevel <= 0) {
            return;
        }

        $this->transactionLevel--;

        if ($this->transactionLevel === 0) {
            $this->pdo->rollBack();

            return;
        }

        $this->pdo->exec($this->savepointStatement('ROLLBACK TO SAVEPOINT'));
        $this->pdo->exec($this->savepointStatement('RELEASE SAVEPOINT'));
    }

    /**
     * Performs the transaction operation.
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();

            return $result;
        } catch (Throwable $exception) {
            $this->rollBack();

            throw $exception;
        }
    }

    /**
     * Performs the pdo operation.
     */
    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * @param array<array-key, mixed> $bindings
     */
    private function report(
        string $sql,
        array $bindings,
        int $started,
        bool $successful,
        ?Throwable $exception = null,
    ): void {
        $this->reporter->report(new QueryExecution(
            connection: $this->name,
            sql: $sql,
            bindings: $bindings,
            durationMs: round((hrtime(true) - $started) / 1_000_000, 3),
            successful: $successful,
            exception: $exception,
        ));
    }

    private function savepointStatement(string $command): string
    {
        return sprintf('%s lpwork_trans_%d', $command, $this->transactionLevel);
    }
}
