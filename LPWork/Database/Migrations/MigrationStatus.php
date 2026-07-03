<?php

declare(strict_types=1);

namespace LPWork\Database\Migrations;

/**
 * Represents the migration status framework component.
 */
final readonly class MigrationStatus
{
    /**
     * Creates a new MigrationStatus instance.
     */
    public function __construct(
        public string $connection,
        public string $migration,
        public bool $ran,
        public ?int $batch = null,
        public ?string $executedAt = null,
    ) {}
}
