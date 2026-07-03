<?php

declare(strict_types=1);

namespace LPWork\Database;

use LPWork\Database\Enums\FetchMode;
use PDOStatement;

/**
 * Represents the result of query result work.
 */
final class QueryResult
{
    /**
     * Creates a new QueryResult instance.
     */
    public function __construct(
        private readonly PDOStatement $statement,
    ) {}

    /**
     * @return list<array<string, mixed>|object>
     */
    public function all(FetchMode $fetchMode = FetchMode::Associative): array
    {
        $rows = $this->statement->fetchAll($fetchMode->pdoMode());
        $normalizedRows = [];

        foreach ($rows as $row) {
            $normalizedRow = $this->normalizeRow($row);

            if ($normalizedRow !== null) {
                $normalizedRows[] = $normalizedRow;
            }
        }

        return $normalizedRows;
    }

    /**
     * @return array<string, mixed>|object|null
     */
    public function first(FetchMode $fetchMode = FetchMode::Associative): array|object|null
    {
        $row = $this->statement->fetch($fetchMode->pdoMode());

        if ($row === false) {
            return null;
        }

        return $this->normalizeRow($row);
    }

    /**
     * Returns value.
     */
    public function value(): mixed
    {
        $value = $this->statement->fetchColumn();

        return $value === false ? null : $value;
    }

    /**
     * Performs the affected rows operation.
     */
    public function affectedRows(): int
    {
        return $this->statement->rowCount();
    }

    /**
     * @return array<string, mixed>|object|null
     */
    private function normalizeRow(mixed $row): array|object|null
    {
        if (is_object($row)) {
            return $row;
        }

        if (!is_array($row)) {
            return null;
        }

        $normalized = [];

        foreach ($row as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}
