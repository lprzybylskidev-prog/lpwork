<?php

declare(strict_types=1);

namespace LPWork\Database;

use Throwable;

/**
 * Represents the query execution framework component.
 */
final readonly class QueryExecution
{
    /**
     * @param array<array-key, mixed> $bindings
     */
    public function __construct(
        public string $connection,
        public string $sql,
        public array $bindings,
        public float $durationMs,
        public bool $successful,
        public ?Throwable $exception = null,
    ) {}
}
