<?php

declare(strict_types=1);

namespace LPWork\Database\Migrations;

/**
 * Represents the migration execution framework component.
 */
final readonly class MigrationExecution
{
    /**
     * Creates a new MigrationExecution instance.
     */
    public function __construct(
        public string $connection,
        public string $migration,
        public int $batch,
    ) {}
}
