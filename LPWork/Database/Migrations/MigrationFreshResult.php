<?php

declare(strict_types=1);

namespace LPWork\Database\Migrations;

/**
 * Represents the result of migration fresh result work.
 */
final readonly class MigrationFreshResult
{
    /**
     * @param list<MigrationExecution> $rolledBack
     * @param list<MigrationExecution> $migrated
     */
    public function __construct(
        public array $rolledBack,
        public array $migrated,
    ) {}
}
