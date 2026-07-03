<?php

declare(strict_types=1);

namespace LPWork\Database\Contracts;

use LPWork\Database\Enums\FetchMode;
use LPWork\Database\QueryResult;
use PDO;

/**
 * Defines the contract for connection.
 */
interface Connection
{
    /**
     * @param array<array-key, mixed> $bindings
     * @return list<array<string, mixed>|object>
     */
    public function select(string $sql, array $bindings = [], FetchMode $fetchMode = FetchMode::Associative): array;

    /**
     * @param array<array-key, mixed> $bindings
     */
    public function statement(string $sql, array $bindings = []): int;

    /**
     * @param array<array-key, mixed> $bindings
     */
    public function query(string $sql, array $bindings = []): QueryResult;

    /**
     * Performs the begin transaction operation.
     */
    public function beginTransaction(): void;

    /**
     * Performs the commit operation.
     */
    public function commit(): void;

    /**
     * Performs the roll back operation.
     */
    public function rollBack(): void;

    /**
     * @template TReturn
     * @param callable(self): TReturn $callback
     * @return TReturn
     */
    public function transaction(callable $callback): mixed;

    /**
     * Performs the pdo operation.
     */
    public function pdo(): PDO;
}
