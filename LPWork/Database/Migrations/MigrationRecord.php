<?php

declare(strict_types=1);

namespace LPWork\Database\Migrations;

/**
 * Represents the migration record framework component.
 */
final readonly class MigrationRecord
{
    /**
     * Creates a new MigrationRecord instance.
     */
    public function __construct(
        public string $migration,
        public int $batch,
        public string $executedAt,
    ) {}
}
