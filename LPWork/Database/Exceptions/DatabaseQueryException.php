<?php

declare(strict_types=1);

namespace LPWork\Database\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Reports database query exception failures.
 */
final class DatabaseQueryException extends RuntimeException
{
    /**
     * @param array<array-key, mixed> $bindings
     */
    private function __construct(
        private readonly string $connection,
        private readonly string $sql,
        private readonly array $bindings,
        Throwable $previous,
    ) {
        parent::__construct(sprintf('Database query failed on connection [%s].', $connection), previous: $previous);
    }

    /**
     * @param array<array-key, mixed> $bindings
     */
    public static function failed(string $connection, string $sql, array $bindings, Throwable $previous): self
    {
        return new self($connection, $sql, $bindings, $previous);
    }

    /**
     * Returns connection.
     */
    public function connection(): string
    {
        return $this->connection;
    }

    /**
     * Performs the sql operation.
     */
    public function sql(): string
    {
        return $this->sql;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function bindings(): array
    {
        return $this->bindings;
    }
}
